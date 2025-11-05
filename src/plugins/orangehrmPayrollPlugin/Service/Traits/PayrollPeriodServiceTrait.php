<?php

namespace OrangeHRM\Payroll\Service\Traits;

use OrangeHRM\Payroll\Service\PayrollPeriodService;

trait PayrollPeriodServiceTrait
{
    private ?PayrollPeriodService $payrollPeriodService = null;

    /**
     * @return PayrollPeriodService
     */
    public function getPayrollPeriodService(): PayrollPeriodService
    {
        if (!$this->payrollPeriodService instanceof PayrollPeriodService) {
            $this->payrollPeriodService = new PayrollPeriodService();
        }
        return $this->payrollPeriodService;
    }

}