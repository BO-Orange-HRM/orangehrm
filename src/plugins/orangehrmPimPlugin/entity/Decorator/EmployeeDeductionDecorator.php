<?php
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 */

namespace OrangeHRM\Entity\Decorator;

use OrangeHRM\Core\Traits\ServiceContainerTrait;
use OrangeHRM\Entity\EmployeeDeduction;
use OrangeHRM\Framework\Services;

class EmployeeDeductionDecorator
{
    use ServiceContainerTrait;

    private EmployeeDeduction $employeeDeduction;

    public function __construct(EmployeeDeduction $employeeDeduction)
    {
        $this->employeeDeduction = $employeeDeduction;
    }

    /**
     * @return EmployeeDeduction
     */
    public function getEmployeeDeduction(): EmployeeDeduction
    {
        return $this->employeeDeduction;
    }

    // Add any additional business logic methods here
    // For example, formatting methods or calculations

    public function getFormattedAmount(): string
    {
        // Example: Format amount with currency symbol
        $amount = $this->employeeDeduction->getAmount();
        // You can get the currency from employee's salary if needed
        return number_format($amount, 2);
    }
}
