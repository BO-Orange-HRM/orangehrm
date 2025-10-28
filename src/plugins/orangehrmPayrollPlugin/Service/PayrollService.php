<?php

namespace OrangeHRM\Payroll\Service;

use OrangeHRM\Entity\Payroll;
use OrangeHRM\Entity\PayrollComponent;
use OrangeHRM\Payroll\Dao\PayrollDao;
use OrangeHRM\Payroll\Dto\PayrollSearchFilterParams;

class PayrollService
{
    private ?PayrollDao $payrollDao = null;

    /**
     * @return PayrollDao
     */
    public function getPayrollDao(): PayrollDao
    {
        if (!$this->payrollDao instanceof PayrollDao) {
            $this->payrollDao = new PayrollDao();
        }
        return $this->payrollDao;
    }

    /**
     * @param Payroll $payroll
     * @return Payroll
     */
    public function savePayroll(Payroll $payroll): Payroll
    {
        // Calculate net salary
        $netSalary = $payroll->getBasicSalary()
            + ($payroll->getAllowances() ?? 0)
            - ($payroll->getDeductions() ?? 0);
        $payroll->setNetSalary($netSalary);

        if (!$payroll->getCreatedAt()) {
            $payroll->setCreatedAt(new \DateTime());
        }

        return $this->getPayrollDao()->savePayroll($payroll);
    }

    /**
     * @param int $id
     * @return Payroll|null
     */
    public function getPayrollById(int $id): ?Payroll
    {
        return $this->getPayrollDao()->getPayrollById($id);
    }

    /**
     * @param PayrollSearchFilterParams $payrollSearchFilterParams
     * @return array
     */
    public function getPayrollList(PayrollSearchFilterParams $payrollSearchFilterParams): array
    {
        return $this->getPayrollDao()->getPayrollList($payrollSearchFilterParams);
    }

    /**
     * @param PayrollSearchFilterParams $payrollSearchFilterParams
     * @return int
     */
    public function getPayrollCount(PayrollSearchFilterParams $payrollSearchFilterParams): int
    {
        return $this->getPayrollDao()->getPayrollCount($payrollSearchFilterParams);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function deletePayroll(int $id): bool
    {
        return $this->getPayrollDao()->deletePayroll($id);
    }

    /**
     * @param PayrollComponent $component
     * @return PayrollComponent
     */
    public function savePayrollComponent(PayrollComponent $component): PayrollComponent
    {
        return $this->getPayrollDao()->savePayrollComponent($component);
    }

    /**
     * @return array
     */
    public function getPayrollComponents(): array
    {
        return $this->getPayrollDao()->getPayrollComponents();
    }

    /**
     * @param int $employeeNumber
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     * @return float
     */
    public function calculateTotalPayroll(int $employeeNumber, \DateTime $fromDate, \DateTime $toDate): float
    {
        $filterParams = new PayrollSearchFilterParams();
        $filterParams->setEmployeeNumber($employeeNumber);
        $filterParams->setFromDate($fromDate);
        $filterParams->setToDate($toDate);
        $filterParams->setStatus('paid');

        $payrolls = $this->getPayrollList($filterParams);

        $total = 0;
        foreach ($payrolls as $payroll) {
            $total += $payroll->getNetSalary();
        }

        return $total;
    }
}