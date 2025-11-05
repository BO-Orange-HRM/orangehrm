<?php

namespace OrangeHRM\Payroll\Service;

use DateTime;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\TransactionRequiredException;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\PayrollPeriod;
use OrangeHRM\Payroll\Dao\PayrollPeriodDao;
use Exception;

class PayrollPeriodService
{
    use EntityManagerHelperTrait;

    private ?PayrollPeriodDao $payrollPeriodDao = null;

    /**
     * Create period and automatically populate with employee payroll records
     * @throws Exception
     */
    public function createPeriod(array $data): PayrollPeriod
    {
        // Start transaction
        $this->beginTransaction();

        try {
            // Create the payroll period
            $period = new PayrollPeriod();
            $period->setStartDate(new \DateTime($data['startDate']));
            $period->setEndDate(new \DateTime($data['endDate']));
            $period->setProcessedAt(new \DateTime($data['paymentDate']));
            $period->setFrequency($data['frequency']);
            $period->setStatus('Draft');
            $period->setCreatedAt(new \DateTime());

            // Validate dates
            if ($period->getStartDate() > $period->getEndDate()) {
                throw new Exception("Start date cannot be after end date.");
            }

            $name = $this->getPayPeriodName($period->getStartDate()->format('Y-m-d'), $period->getEndDate()->format('Y-m-d'));
            $period->setName($name);

            // Save the period
            $savedPeriod = $this->getPayrollPeriodDao()->save($period);

            $this->getPayrollPeriodDao()->getEntityManager()->flush();

            // Create payroll records with calculated amounts for all active employees
            $employeeCount = $this->getPayrollPeriodDao()
                ->createPayrollRecordsForPeriod($savedPeriod->getId());

            // Update period total amount
            $this->getPayrollPeriodDao()->updatePeriodTotalAmount($savedPeriod->getId());

            // Commit transaction
            $this->commitTransaction();

            // Log success
            error_log("Created payroll period {$savedPeriod->getId()} with {$employeeCount} employee records");

            return $savedPeriod;

        } catch (Exception $e) {
            // Rollback on error
            $this->rollBackTransaction();
            throw new Exception("Failed to create payroll period: " . $e->getMessage());
        }
    }

    /**
     * Get payroll period with employee count
     */
    public function getPeriodWithEmployeeCount(int $periodId): array
    {
        $period = $this->getPayrollPeriodDao()->findById($periodId);
        if (!$period) {
            throw new Exception("Payroll period not found.");
        }

        $employeeCount = $this->getPayrollPeriodDao()
            ->getTotalEmployeesInPeriod($periodId);

        return [
            'period' => $period,
            'employeeCount' => $employeeCount,
            'totalAmount' => $period->getTotalAmount() ?? 0.00
        ];
    }

    /**
     * Get detailed payroll period information
     * @throws Exception
     */
    public function getPayrollPeriodDetails(int $periodId): array
    {
        $period = $this->getPayrollPeriodDao()->findById($periodId);
        if (!$period) {
            throw new Exception("Payroll period not found.");
        }

        $overview = $this->getPayrollPeriodDao()->getOverviewMetrics($periodId);
        $employees = $this->getPayrollPeriodDao()->getEmployeesForPeriod($periodId);
        $auditLogs = $this->getPayrollPeriodDao()->getAuditLogsForPeriod($periodId);

        return [
            'period' => [
                'name' => $this->getPayPeriodName($period->getStartDate()->format('Y-m-d'), $period->getEndDate()->format('Y-m-d')),
                'id' => $period->getId(),
                'startDate' => $period->getStartDate()->format('Y-m-d'),
                'endDate' => $period->getEndDate()->format('Y-m-d'),
                'frequency' => $period->getFrequency(),
                'status' => $period->getStatus(),
                'totalAmount' => $period->getTotalAmount(),
                'createdAt' => $period->getCreatedAt()->format('Y-m-d H:i:s'),
                'processedAt' => $period->getProcessedAt() ? $period->getProcessedAt()->format('Y-m-d H:i:s') : null,
            ],
            'overview' => $overview,
            'employees' => $employees,
            'auditLogs' => $auditLogs
        ];
    }

    /**
     * Generates a formatted pay period name based on the given start and end dates.
     *
     * The method calculates whether the start and end dates fall within the same
     * month and year or span different months/years, and returns a formatted string
     * representation accordingly.
     *
     * @param string $startDate The start date of the pay period in 'Y-m-d' format.
     * @param string $endDate The end date of the pay period in 'Y-m-d' format.
     *
     * @return string A formatted string representing the pay period.
     *
     * @throws \Exception If the given dates are invalid or cannot be parsed.
     */
    function getPayPeriodName(string $startDate, string $endDate): string
    {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);

        $startMonth = $start->format('F');
        $endMonth = $end->format('F');
        $startYear = $start->format('Y');
        $endYear = $end->format('Y');

        if ($startMonth === $endMonth && $startYear === $endYear) {
            return sprintf('%s %s Period', $startMonth, $startYear);
        }

        return sprintf('%s/%s %s Period', $startMonth, $endMonth, $endYear);
    }


    /**
     * Get employee payroll details within a period
     */
    public function getEmployeePayrollDetails(int $periodId, int $empNumber): ?array
    {
        return $this->getPayrollPeriodDao()->getEmployeePayrollDetails($periodId, $empNumber);
    }

    /**
     * @throws NotSupported
     */
    public function getAll(): array
    {
        return $this->getPayrollPeriodDao()->getAll();
    }

    /**
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     * @throws ORMException
     */
    public function updateStatus(int $id, string $status): PayrollPeriod
    {
        $period = $this->getPayrollPeriodDao()->findById($id);
        if (!$period) throw new Exception("Payroll period not found.");
        $period->setStatus($status);
        return $this->getPayrollPeriodDao()->save($period);
    }

    /**
     * @return PayrollPeriodDao
     */
    public function getPayrollPeriodDao(): PayrollPeriodDao
    {
        if (!$this->payrollPeriodDao instanceof PayrollPeriodDao) {
            $this->payrollPeriodDao = new PayrollPeriodDao($this->getEntityManager());
        }
        return $this->payrollPeriodDao;
    }

    /**
     * @return PayrollPeriod|null
     */
    public function getActivePeriod(): ?PayrollPeriod
    {
        return $this->getPayrollPeriodDao()->getActivePeriod();
    }

    public function getRecentPeriods(int $limit = 5): array
    {
        return $this->getPayrollPeriodDao()->getRecentPeriods($limit);
    }

    public function getTotalEmployeesInActivePeriod(): int
    {
        $activePeriod = $this->getActivePeriod();
        if (!$activePeriod) {
            return 0;
        }
        return $this->getPayrollPeriodDao()->getTotalEmployeesInPeriod($activePeriod->getId());
    }

    public function getTotalAmountForActivePeriod(): float
    {
        $activePeriod = $this->getActivePeriod();
        if (!$activePeriod) {
            return 0.0;
        }
        return $this->getPayrollPeriodDao()->getTotalAmountForPeriod($activePeriod->getId());
    }

    public function getPendingApprovalsCount(): int
    {
        return $this->getPayrollPeriodDao()->getPendingApprovalsCount();
    }

    public function getLastPayrollRunDate(): ?string
    {
        return $this->getPayrollPeriodDao()->getLastPayrollRunDate();
    }

    public function getPayrollAlerts(): array
    {
        $alerts = [];

        $pendingCount = $this->getPendingApprovalsCount();
        if ($pendingCount > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "You have {$pendingCount} payroll entries pending approval"
            ];
        }

        $activePeriod = $this->getActivePeriod();
        if ($activePeriod) {
            $daysUntilEnd = (new \DateTime())->diff($activePeriod->getEndDate())->days;
            if ($daysUntilEnd <= 3) {
                $alerts[] = [
                    'type' => 'info',
                    'message' => "Current payroll period ends in {$daysUntilEnd} days"
                ];
            }
        }

        return $alerts;
    }

    public function savePayrollPeriod(PayrollPeriod $period): PayrollPeriod
    {
        if (!$period->getCreatedAt()) {
            $period->setCreatedAt(new \DateTime());
        }
        return $this->getPayrollPeriodDao()->savePayrollPeriod($period);
    }
}