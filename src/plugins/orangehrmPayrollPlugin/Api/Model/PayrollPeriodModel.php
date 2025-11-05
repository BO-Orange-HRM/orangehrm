<?php
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software: you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with OrangeHRM.
 * If not, see <https://www.gnu.org/licenses/>.
 */

namespace OrangeHRM\Payroll\Api\Model;

use OrangeHRM\Core\Api\V2\Serializer\ModelTrait;
use OrangeHRM\Core\Api\V2\Serializer\Normalizable;
use OrangeHRM\Entity\PayrollPeriod;

/**
 * @OA\Schema(
 *     schema="Payroll-PayrollPeriodModel",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="October 2025"),
 *     @OA\Property(property="startDate", type="string", format="date", example="2025-10-01"),
 *     @OA\Property(property="endDate", type="string", format="date", example="2025-10-31"),
 *     @OA\Property(property="status", type="string", example="Processed"),
 *     @OA\Property(property="totalAmount", type="number", format="float", example=54000.75),
 *     @OA\Property(property="createdAt", type="string", format="date-time", example="2025-10-31 14:23:00"),
 *     @OA\Property(property="processedAt", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="frequency", type="string", nullable=true, example="Monthly"),
 *     @OA\Property(property="paymentDate", type="string", format="date", example="2025-11-01")
 * )
 */
class PayrollPeriodModel implements Normalizable
{
    use ModelTrait;

    public function __construct(PayrollPeriod $payrollPeriod)
    {
        $this->setEntity($payrollPeriod);

        $this->setFilters([
            'id',
            'name',
            'startDate',
            'endDate',
            'status',
            'totalAmount',
            'createdAt',
            'processedAt',
            'frequency',
        ]);

        $this->setAttributeNames([
            'id',
            'name',
            'startDate',
            'endDate',
            'status',
            'totalAmount',
            'createdAt',
            'processedAt',
            'frequency',
        ]);
    }
}
