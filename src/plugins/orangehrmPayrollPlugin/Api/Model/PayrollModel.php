<?php

namespace OrangeHRM\Payroll\Api\Model;

use OrangeHRM\Core\Api\V2\Serializer\ModelTrait;
use OrangeHRM\Core\Api\V2\Serializer\Normalizable;
use OrangeHRM\Entity\Payroll;

/**
 * @OA\Schema(
 *     schema="Payroll-PayrollModel",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="employee", type="object",
 *         @OA\Property(property="empNumber", type="integer"),
 *         @OA\Property(property="firstName", type="string"),
 *         @OA\Property(property="lastName", type="string"),
 *         @OA\Property(property="employeeId", type="string")
 *     ),
 *     @OA\Property(property="basicSalary", type="number"),
 *     @OA\Property(property="allowances", type="number"),
 *     @OA\Property(property="deductions", type="number"),
 *     @OA\Property(property="netSalary", type="number"),
 *     @OA\Property(property="currency", type="string"),
 *     @OA\Property(property="paymentDate", type="string", format="date"),
 *     @OA\Property(property="status", type="string")
 * )
 */
class PayrollModel implements Normalizable
{
    use ModelTrait;

    public function __construct(Payroll $payroll)
    {
        $this->setEntity($payroll);
        $this->setFilters([
            'id',
            'basicSalary',
            'allowances',
            'deductions',
            'netSalary',
            'currency',
            ['getPaymentDate', 'Y-m-d'],
            'status',
            ['getEmployee', 'empNumber'],
            ['getEmployee', 'getFirstName'],
            ['getEmployee', 'getLastName'],
            ['getEmployee', 'getEmployeeId'],
        ]);
        $this->setAttributeNames([
            'id',
            'basicSalary',
            'allowances',
            'deductions',
            'netSalary',
            'currency',
            'paymentDate',
            'status',
            ['employee', 'empNumber'],
            ['employee', 'firstName'],
            ['employee', 'lastName'],
            ['employee', 'employeeId'],
        ]);
    }
}