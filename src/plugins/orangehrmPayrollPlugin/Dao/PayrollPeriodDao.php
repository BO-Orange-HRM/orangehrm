<?php

namespace OrangeHRM\Payroll\Dao;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\TransactionRequiredException;
use OrangeHRM\Core\Dao\BaseDao;
use OrangeHRM\Entity\PayrollPeriod;
use OrangeHRM\Payroll\Service\PayrollCalculationService;

class PayrollPeriodDao extends BaseDao
{
    private EntityManager $entityManager;
    private ?PayrollCalculationService $payrollCalculationService = null;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Get entity manager
     */
    public function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    /**
     * Get payroll calculation service
     */
    private function getPayrollCalculationService(): PayrollCalculationService
    {
        if (!$this->payrollCalculationService instanceof PayrollCalculationService) {
            $this->payrollCalculationService = new PayrollCalculationService($this->entityManager);
        }
        return $this->payrollCalculationService;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function save(PayrollPeriod $period): PayrollPeriod
    {
        $this->entityManager->persist($period);
        $this->entityManager->flush();
        return $period;
    }

    /**
     * @throws NotSupported
     */
    public function getAll(): array
    {
        return $this->entityManager->getRepository(PayrollPeriod::class)->findAll();
    }

    /**
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     * @throws ORMException
     */
    public function findById(int $id): ?PayrollPeriod
    {
        return $this->entityManager->find(PayrollPeriod::class, $id);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function delete(PayrollPeriod $period): void
    {
        $this->entityManager->remove($period);
        $this->entityManager->flush();
    }

    /**
     * Get overview metrics - FIXED to use correct table
     */
    public function getOverviewMetrics(int $pPid): array
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->select(
            'COUNT(p.id) AS total_employees',
            'SUM(p.gross_amount) AS total_gross',
            'SUM(p.deductions) AS total_deductions',
            'SUM(p.net_amount) AS total_net'
        )
            ->from('ohrm_payroll', 'p')
            ->where('p.pay_period_id = :pPid')
            ->setParameter('pPid', $pPid);

        $row = $qb->executeQuery()->fetchAssociative();

        return [
            ['label' => 'payroll.total_employees', 'value' => (int)($row['total_employees'] ?? 0)],
            ['label' => 'payroll.total_gross_pay', 'value' => number_format((float)($row['total_gross'] ?? 0), 2)],
            ['label' => 'payroll.total_deductions', 'value' => number_format((float)($row['total_deductions'] ?? 0), 2)],
            ['label' => 'payroll.total_net_pay', 'value' => number_format((float)($row['total_net'] ?? 0), 2)],
        ];
    }

    /**
     * Create payroll records for all active employees with calculated amounts
     */
    public function createPayrollRecordsForPeriod(int $periodId): int
    {
        $employees = $this->getPayrollCalculationService()->getEmployeesWithSalaryInfo();
        $count = 0;
        $errors = [];

        $this->getEntityManager()->flush();

        foreach ($employees as $employee) {
            $empNumber = (int)$employee['emp_number'];

            try {
                $payrollData = $this->getPayrollCalculationService()->calculateEmployeePayroll($empNumber);

                if (!$payrollData) {
                    $errors[] = "Employee {$empNumber}: No salary information found";
                    continue;
                }

                $conn = $this->getEntityManager()->getConnection();

                $conn->insert('ohrm_payroll', [
                    'pay_period_id' => $periodId,
                    'employee_id' => $empNumber,
                    'gross_amount' => $payrollData['gross_salary'],
                    'deductions' => $payrollData['total_deductions'],
                    'net_amount' => $payrollData['net_salary'],
                    'currency_id' => $payrollData['currency_id'],
                    'pay_period_code' => $payrollData['payperiod_code'],
                    'status' => 'pending',
                    'created_at' => (new \DateTime())->format('Y-m-d H:i:s')
                ]);

                $payrollId = (int)$conn->lastInsertId();

                $this->createPayrollItems($payrollId, $payrollData);

                $count++;

            } catch (\Exception $e) {
                $errors[] = "Employee {$empNumber}: " . $e->getMessage();
                error_log("Error creating payroll for employee {$empNumber}: " . $e->getMessage());
            }
        }

        if (!empty($errors)) {
            error_log("Payroll creation errors:\n" . implode("\n", $errors));
        }

        return $count;
    }

    /**
     * Create detailed payroll items (earnings and deductions breakdown)
     */
    private function createPayrollItems(int $payrollId, array $payrollData): void
    {
        $conn = $this->getEntityManager()->getConnection();

        foreach ($payrollData['salary_components'] as $component) {
            $conn->insert('ohrm_payroll_item', [
                'payroll_id' => $payrollId,
                'item_name' => $component['name'],
                'item_type' => 'earning',
                'amount' => $component['amount'],
                'remarks' => 'Salary component'
            ]);
        }

        foreach ($payrollData['direct_debit_breakdown'] as $deduction) {
            $conn->insert('ohrm_payroll_item', [
                'payroll_id' => $payrollId,
                'item_name' => $deduction['name'],
                'item_type' => 'deduction',
                'amount' => $deduction['amount'],
                'remarks' => "Account: ***{$deduction['account']}"
            ]);
        }

        foreach ($payrollData['tax_breakdown'] as $tax) {
            $conn->insert('ohrm_payroll_item', [
                'payroll_id' => $payrollId,
                'item_name' => $tax['name'],
                'item_type' => 'deduction',
                'amount' => $tax['amount'],
                'remarks' => "Tax rate: {$tax['rate']}%"
            ]);
        }
    }

    /**
     * Update payroll period total amount - ADDED THIS METHOD
     */
    public function updatePeriodTotalAmount(int $periodId): void
    {
        $this->getEntityManager()->flush();
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->select('SUM(p.net_amount) as total')
            ->from('ohrm_payroll', 'p')
            ->where('p.pay_period_id = :periodId')
            ->setParameter('periodId', $periodId);

        $result = $qb->executeQuery()->fetchAssociative();
        $totalAmount = (float)($result['total'] ?? 0);

        $updateQb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $updateQb->update('ohrm_payroll_period', 'pp')
            ->set('pp.total_amount', ':totalAmount')
            ->where('pp.id = :periodId')
            ->setParameter('totalAmount', $totalAmount)
            ->setParameter('periodId', $periodId)
            ->executeStatement();
    }

    /**
     * Get employees for period - FIXED to use correct table
     */
    public function getEmployeesForPeriod(int $pPid): array
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->select(
            'p.id',
            'p.employee_id as emp_number',
            "CONCAT(e.emp_firstname, ' ', e.emp_lastname) AS employee_name",
            'p.gross_amount as gross_pay',
            'p.deductions as total_deductions',
            'p.net_amount as net_pay',
            's.name AS department',
            'p.status'
        )
            ->from('ohrm_payroll', 'p')
            ->leftJoin('p', 'hs_hr_employee', 'e', 'e.emp_number = p.employee_id')
            ->leftJoin('e', 'ohrm_subunit', 's', 's.id = e.work_station')
            ->where('p.pay_period_id = :pPid')
            ->setParameter('pPid', $pPid)
            ->orderBy('e.emp_lastname', 'ASC');

        $rows = $qb->executeQuery()->fetchAllAssociative();

        return array_map(static fn($row) => [
            'employeeName' => $row['employee_name'],
            'grossPay' => (float)$row['gross_pay'],
            'deductions' => (float)$row['total_deductions'],
            'netPay' => (float)$row['net_pay'],
            'department' => $row['department'] ?? '-',
            'status' => $row['status'],
        ], $rows);
    }

    /**
     * Get detailed payroll breakdown for an employee in a period
     */
    public function getEmployeePayrollDetails(int $periodId, int $empNumber): ?array
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->select(
            'p.id as payroll_id',
            'p.gross_amount',
            'p.deductions',
            'p.net_amount',
            'p.status',
            'e.emp_firstname',
            'e.emp_lastname',
            'e.employee_id'
        )
            ->from('ohrm_payroll', 'p')
            ->innerJoin('p', 'hs_hr_employee', 'e', 'p.employee_id = e.emp_number')
            ->where('p.pay_period_id = :periodId')
            ->andWhere('p.employee_id = :empNumber')
            ->setParameter('periodId', $periodId)
            ->setParameter('empNumber', $empNumber);

        $payroll = $qb->executeQuery()->fetchAssociative();

        if (!$payroll) {
            return null;
        }

        $itemsQb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $itemsQb->select(
            'pi.item_name',
            'pi.item_type',
            'pi.amount',
            'pi.remarks'
        )
            ->from('ohrm_payroll_item', 'pi')
            ->where('pi.payroll_id = :payrollId')
            ->setParameter('payrollId', $payroll['payroll_id'])
            ->orderBy('pi.item_type', 'DESC')
            ->addOrderBy('pi.item_name', 'ASC');

        $items = $itemsQb->executeQuery()->fetchAllAssociative();

        $earnings = array_filter($items, fn($item) => $item['item_type'] === 'earning');
        $deductions = array_filter($items, fn($item) => $item['item_type'] === 'deduction');

        return [
            'employee' => [
                'emp_number' => $empNumber,
                'name' => $payroll['emp_firstname'] . ' ' . $payroll['emp_lastname'],
                'employee_id' => $payroll['employee_id']
            ],
            'summary' => [
                'gross_amount' => (float)$payroll['gross_amount'],
                'total_deductions' => (float)$payroll['deductions'],
                'net_amount' => (float)$payroll['net_amount'],
                'status' => $payroll['status']
            ],
            'earnings' => array_values($earnings),
            'deductions' => array_values($deductions)
        ];
    }

    public function getAuditLogsForPeriod(int $pPid): array
    {
        // Check if audit log table exists
        try {
            $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

            $qb->select('id', 'message', 'created_at')
                ->from('ohrm_payroll_audit_log', 'l')
                ->where('l.pay_period_id = :pPid')
                ->setParameter('pPid', $pPid)
                ->orderBy('l.created_at', 'DESC');

            $rows = $qb->executeQuery()->fetchAllAssociative();

            return array_map(static fn($row) => [
                'id' => (int)$row['id'],
                'message' => $row['message'],
                'timestamp' => $row['created_at'],
            ], $rows);
        } catch (\Exception $e) {
            // Table doesn't exist yet, return empty array
            return [];
        }
    }

    /**
     * Get the currently active payroll period
     */
    public function getActivePeriod(): ?PayrollPeriod
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('pp')
            ->from(PayrollPeriod::class, 'pp')
            ->where('pp.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('pp.startDate', 'DESC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Get recent payroll periods
     */
    public function getRecentPeriods(int $limit = 5): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('pp')
            ->from(PayrollPeriod::class, 'pp')
            ->orderBy('pp.startDate', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function savePayrollPeriod(PayrollPeriod $period): PayrollPeriod
    {
        $this->entityManager->persist($period);
        $this->entityManager->flush();
        return $period;
    }

    public function getPayrollPeriodById(int $id): ?PayrollPeriod
    {
        return $this->getEntityManager()->getRepository(PayrollPeriod::class)->find($id);
    }

    /**
     * Get total employees in a period - FIXED
     */
    public function getTotalEmployeesInPeriod(int $periodId): int
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->select('COUNT(DISTINCT p.employee_id)')
            ->from('ohrm_payroll', 'p')
            ->where('p.pay_period_id = :periodId')
            ->setParameter('periodId', $periodId);

        return (int)$qb->executeQuery()->fetchOne();
    }

    /**
     * Get total amount for a period - FIXED
     */
    public function getTotalAmountForPeriod(int $periodId): float
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->select('SUM(p.net_amount)')
            ->from('ohrm_payroll', 'p')
            ->where('p.pay_period_id = :periodId')
            ->setParameter('periodId', $periodId);

        return (float)($qb->executeQuery()->fetchOne() ?? 0);
    }

    /**
     * Get count of pending approvals - FIXED
     */
    public function getPendingApprovalsCount(): int
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->select('COUNT(p.id)')
            ->from('ohrm_payroll', 'p')
            ->where('p.status = :status')
            ->setParameter('status', 'pending');

        return (int)$qb->executeQuery()->fetchOne();
    }

    /**
     * Get last payroll run date
     */
    public function getLastPayrollRunDate(): ?string
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->select('pp.processed_at')
            ->from('ohrm_payroll_period', 'pp')
            ->where('pp.processed_at IS NOT NULL')
            ->orderBy('pp.processed_at', 'DESC')
            ->setMaxResults(1);

        $result = $qb->executeQuery()->fetchOne();

        return $result ? date('Y-m-d', strtotime($result)) : null;
    }
}