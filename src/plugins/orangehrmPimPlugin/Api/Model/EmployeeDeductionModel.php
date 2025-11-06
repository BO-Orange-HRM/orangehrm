<?php
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 */

namespace OrangeHRM\Pim\Api\Model;

use OrangeHRM\Core\Api\V2\Serializer\ModelTrait;
use OrangeHRM\Core\Api\V2\Serializer\Normalizable;
use OrangeHRM\Entity\EmployeeDeduction;

/**
 * @OA\Schema(
 *     schema="Pim-EmployeeDeductionModel",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="amount", type="number"),
 *     @OA\Property(property="comment", type="string"),
 *     @OA\Property(property="effectiveDate", type="string", format="date"),
 *     @OA\Property(property="employeeId", type="integer")
 * )
 */
class EmployeeDeductionModel implements Normalizable
{
    use ModelTrait;

    public function __construct(EmployeeDeduction $deduction)
    {
        $this->setEntity($deduction);
        $this->setFilters(
            [
                'id',
                'name',
                'amount',
                'comment',
                ['getEffectiveDate', 'format', ['Y-m-d']],
                ['getEmployee', 'getEmpNumber']
            ]
        );
        $this->setAttributeNames(
            [
                'id',
                'name',
                'amount',
                'comment',
                'effectiveDate',
                'employeeId'
            ]
        );
    }
}
