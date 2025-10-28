<?php

namespace OrangeHRM\Payroll\Service\Traits;

use OrangeHRM\Core\Traits\ServiceContainerTrait;
use OrangeHRM\Framework\Services;

trait EmployeePayrollOverviewServiceTrait
{
    use ServiceContainerTrait;

    /**
     * @throws \Exception
     */
    public function getEmployeeSalaryService(): object
    {
        return $this->getContainer()->get(Services::EMPLOYEE_SALARY_SERVICE);
    }
}