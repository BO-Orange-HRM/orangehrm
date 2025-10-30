<?php

namespace OrangeHRM\Payroll\Service;

use Doctrine\ORM\EntityManager;

class PayrollCalculationService
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Get entity manager
     */
    private function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    /**
     * Get all salary components for an employee
     */
    public function getEmployeeSalaryComponents(int $empNumber): array
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->select(
            'ebs.id as salary_id',
            'ebs.ebsal_basic_salary as amount',
            'ebs.currency_id',
            'ebs.payperiod_code',
            'ebs.salary_component',
            'ct.currency_name',
            'pp.payperiod_name'
        )
            ->from('hs_hr_emp_basicsalary', 'ebs')
            ->leftJoin('ebs', 'hs_hr_currency_type', 'ct', 'ebs.currency_id = ct.currency_id')
            ->leftJoin('ebs', 'hs_hr_payperiod', 'pp', 'ebs.payperiod_code = pp.payperiod_code')
            ->where('ebs.emp_number = :empNumber')
            ->setParameter('empNumber', $empNumber)
            ->orderBy('ebs.id', 'ASC');

        $results = $qb->executeQuery()->fetchAllAssociative();

        if (empty($results)) {
            return [];
        }

        $components = [];
        foreach ($results as $result) {
            $components[] = [
                'salary_id' => (int)$result['salary_id'],
                'amount' => (float)$result['amount'],
                'currency_id' => $result['currency_id'],
                'payperiod_code' => $result['payperiod_code'],
                'component_name' => $result['salary_component'] ?: 'Basic Salary',
                'currency_name' => $result['currency_name'],
                'payperiod_name' => $result['payperiod_name']
            ];
        }

        return $components;
    }

    /**
     * Get employee total gross salary (sum of all components)
     */
    public function getEmployeeTotalGrossSalary(int $empNumber): ?array
    {
        $components = $this->getEmployeeSalaryComponents($empNumber);

        if (empty($components)) {
            return null;
        }

        // Group by currency to ensure we're summing same currency amounts
        $currencyGroups = [];
        $salaryIds = [];

        foreach ($components as $component) {
            $currencyId = $component['currency_id'];

            if (!isset($currencyGroups[$currencyId])) {
                $currencyGroups[$currencyId] = [
                    'total' => 0.0,
                    'currency_id' => $currencyId,
                    'currency_name' => $component['currency_name'],
                    'payperiod_code' => $component['payperiod_code'],
                    'payperiod_name' => $component['payperiod_name'],
                    'components' => []
                ];
            }

            $currencyGroups[$currencyId]['total'] += $component['amount'];
            $currencyGroups[$currencyId]['components'][] = [
                'name' => $component['component_name'],
                'amount' => $component['amount']
            ];
            $salaryIds[] = $component['salary_id'];
        }

        // If employee has multiple currencies, we'll use the first one (or handle as needed)
        $primaryCurrency = reset($currencyGroups);

        // If mixed currencies, you might want to log a warning
        if (count($currencyGroups) > 1) {
            error_log("Warning: Employee {$empNumber} has salary components in multiple currencies");
        }

        return [
            'emp_number' => $empNumber,
            'total_gross_salary' => $primaryCurrency['total'],
            'currency_id' => $primaryCurrency['currency_id'],
            'currency_name' => $primaryCurrency['currency_name'],
            'payperiod_code' => $primaryCurrency['payperiod_code'],
            'payperiod_name' => $primaryCurrency['payperiod_name'],
            'components' => $primaryCurrency['components'],
            'salary_ids' => $salaryIds,
            'all_currency_groups' => $currencyGroups
        ];
    }

    /**
     * Get employee direct debit deductions for all salary components
     */
    public function getEmployeeDirectDebits(array $salaryIds): array
    {
        if (empty($salaryIds)) {
            return [];
        }

        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->select(
            'dd.id',
            'dd.salary_id',
            'dd.dd_routing_num',
            'dd.dd_account',
            'dd.dd_amount',
            'dd.dd_account_type',
            'dd.dd_transaction_type'
        )
            ->from('hs_hr_emp_directdebit', 'dd')
            ->where($qb->expr()->in('dd.salary_id', ':salaryIds'))
            ->setParameter('salaryIds', $salaryIds, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

        return $qb->executeQuery()->fetchAllAssociative();
    }

    /**
     * Calculate total deductions from direct debits
     */
    public function calculateDirectDebitDeductions(array $salaryIds, float $grossSalary): array
    {
        $directDebits = $this->getEmployeeDirectDebits($salaryIds);
        $totalDeductions = 0.0;
        $deductionBreakdown = [];

        foreach ($directDebits as $debit) {
            $amount = (float)$debit['dd_amount'];
            $transactionType = $debit['dd_transaction_type'];
            $accountType = $debit['dd_account_type'];
            $calculatedAmount = 0.0;

            switch ($transactionType) {
                case 'PERC': // Percentage
                    $calculatedAmount = ($grossSalary * $amount / 100);
                    $description = "{$accountType} - {$amount}%";
                    break;
                case 'FLAT': // Flat amount
                    $calculatedAmount = $amount;
                    $description = "{$accountType} - Flat Amount";
                    break;
                case 'FLATMINUS': // Flat amount minus
                    $calculatedAmount = $amount;
                    $description = "{$accountType} - Flat Minus";
                    break;
                default: // BLANK or other
                    $calculatedAmount = $amount;
                    $description = "{$accountType} - Fixed";
                    break;
            }

            $totalDeductions += $calculatedAmount;
            $deductionBreakdown[] = [
                'name' => $description,
                'amount' => $calculatedAmount,
                'account' => substr($debit['dd_account'], -4)
            ];
        }

        return [
            'total' => $totalDeductions,
            'breakdown' => $deductionBreakdown
        ];
    }

    /**
     * Get employee tax information
     */
    public function getEmployeeTaxInfo(int $empNumber): ?array
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->select(
            'tax.tax_federal_status',
            'tax.tax_federal_exceptions',
            'tax.tax_state',
            'tax.tax_state_status',
            'tax.tax_state_exceptions',
            'tax.tax_unemp_state',
            'tax.tax_work_state'
        )
            ->from('hs_hr_emp_us_tax', 'tax')
            ->where('tax.emp_number = :empNumber')
            ->setParameter('empNumber', $empNumber);

        $result = $qb->executeQuery()->fetchAssociative();

        return $result ?: null;
    }

    /**
     * Calculate tax deductions with breakdown
     */
    public function calculateTaxDeductions(int $empNumber, float $grossSalary): array
    {
        $taxInfo = $this->getEmployeeTaxInfo($empNumber);

        if (!$taxInfo) {
            return [
                'total' => 0.0,
                'breakdown' => []
            ];
        }

        // Simplified tax calculation - customize based on your tax rules
        $federalTaxRate = 0.15;
        $stateTaxRate = 0.05;

        $federalExceptions = (int)($taxInfo['tax_federal_exceptions'] ?? 0);
        $stateExceptions = (int)($taxInfo['tax_state_exceptions'] ?? 0);

        $exemptionAmount = 4000;
        $taxableIncome = max(0, $grossSalary - ($federalExceptions * $exemptionAmount));

        $federalTax = $taxableIncome * $federalTaxRate;
        $stateTax = $taxableIncome * $stateTaxRate;

        $breakdown = [];

        if ($federalTax > 0) {
            $breakdown[] = [
                'name' => "Federal Tax ({$taxInfo['tax_federal_status']})",
                'amount' => $federalTax,
                'rate' => $federalTaxRate * 100
            ];
        }

        if ($stateTax > 0 && !empty($taxInfo['tax_state'])) {
            $breakdown[] = [
                'name' => "State Tax - {$taxInfo['tax_state']} ({$taxInfo['tax_state_status']})",
                'amount' => $stateTax,
                'rate' => $stateTaxRate * 100
            ];
        }

        return [
            'total' => $federalTax + $stateTax,
            'breakdown' => $breakdown
        ];
    }

    /**
     * Get complete payroll calculation for an employee with all components
     */
    public function calculateEmployeePayroll(int $empNumber): ?array
    {
        $salaryInfo = $this->getEmployeeTotalGrossSalary($empNumber);

        if (!$salaryInfo) {
            return null;
        }

        $grossSalary = $salaryInfo['total_gross_salary'];
        $salaryIds = $salaryInfo['salary_ids'];

        $directDebitResult = $this->calculateDirectDebitDeductions($salaryIds, $grossSalary);
        $taxResult = $this->calculateTaxDeductions($empNumber, $grossSalary);

        $totalDeductions = $directDebitResult['total'] + $taxResult['total'];
        $netSalary = $grossSalary - $totalDeductions;

        return [
            'emp_number' => $empNumber,
            'gross_salary' => $grossSalary,
            'salary_components' => $salaryInfo['components'],
            'direct_debit_deductions' => $directDebitResult['total'],
            'direct_debit_breakdown' => $directDebitResult['breakdown'],
            'tax_deductions' => $taxResult['total'],
            'tax_breakdown' => $taxResult['breakdown'],
            'total_deductions' => $totalDeductions,
            'net_salary' => max(0, $netSalary),
            'currency_id' => $salaryInfo['currency_id'],
            'currency_name' => $salaryInfo['currency_name'],
            'payperiod_code' => $salaryInfo['payperiod_code'],
            'payperiod_name' => $salaryInfo['payperiod_name']
        ];
    }

    /**
     * Get employees with their aggregated salary information
     */
    public function getEmployeesWithSalaryInfo(): array
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->select(
            'e.emp_number',
            'e.employee_id',
            'e.emp_firstname',
            'e.emp_lastname',
            'e.emp_middle_name',
            'SUM(ebs.ebsal_basic_salary) as total_salary',
            'COUNT(DISTINCT ebs.id) as component_count',
            'MIN(ebs.currency_id) as currency_id',
            'MIN(ebs.payperiod_code) as payperiod_code'
        )
            ->from('hs_hr_employee', 'e')
            ->innerJoin('e', 'hs_hr_emp_basicsalary', 'ebs', 'e.emp_number = ebs.emp_number')
            ->leftJoin('e', 'ohrm_emp_termination', 'et', 'e.termination_id = et.id')
            ->where($qb->expr()->isNull('et.id'))
            ->andWhere($qb->expr()->isNull('e.purged_at'))
            ->groupBy('e.emp_number', 'e.employee_id', 'e.emp_firstname', 'e.emp_lastname', 'e.emp_middle_name')
            ->orderBy('e.emp_lastname', 'ASC');

        return $qb->executeQuery()->fetchAllAssociative();
    }

    /**
     * Get detailed salary component breakdown for reporting
     */
    public function getSalaryComponentSummary(int $periodId): array
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->select(
            'pi.item_name as component',
            'COUNT(DISTINCT pi.payroll_id) as employee_count',
            'SUM(pi.amount) as total_amount',
            'AVG(pi.amount) as average_amount',
            'MIN(pi.amount) as min_amount',
            'MAX(pi.amount) as max_amount'
        )
            ->from('ohrm_payroll_item', 'pi')
            ->innerJoin('pi', 'ohrm_payroll', 'p', 'pi.payroll_id = p.id')
            ->where('p.payroll_period_id = :periodId')
            ->andWhere('pi.item_type = :itemType')
            ->setParameter('periodId', $periodId)
            ->setParameter('itemType', 'earning')
            ->groupBy('pi.item_name')
            ->orderBy('total_amount', 'DESC');

        return $qb->executeQuery()->fetchAllAssociative();
    }
}