<?php

namespace OrangeHRM\Payroll\Service;

use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Payroll\Dao\EmployeePayrollOverviewDao;

class EmployeePayrollOverviewService
{
    use EntityManagerHelperTrait;
    private EmployeePayrollOverviewDao $dao;

    public function __construct(EmployeePayrollOverviewDao $dao)
    {
        $this->dao = $dao;
    }

    public function getEmployees(array $filters, int $offset, int $limit, array $sort): array
    {
        return [
            'data' => $this->dao->getEmployees($filters, $offset, $limit, $sort),
            'total' => $this->dao->getTotalEmployees($filters)
        ];
    }
}

