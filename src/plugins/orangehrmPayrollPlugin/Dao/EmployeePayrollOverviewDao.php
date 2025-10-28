<?php

namespace OrangeHRM\Payroll\Dao;

use OrangeHRM\Core\Dao\BaseDao;
use OrangeHRM\Payroll\Api\Model\EmployeePayrollOverviewModel;

class EmployeePayrollOverviewDao extends BaseDao
{
    public function getEmployees(array $filters, int $offset, int $limit, array $sort): array
    {
        $qb = $this->createQueryBuilder(EmployeePayrollOverviewModel::class, 'e')
            ->addSelect('e.empNumber, e.name, e.salary, e.department');

        // Apply filters
        if (!empty($filters['employeeName'])) {
            $qb->andWhere($qb->expr()->like('e.name', ':employeeName'))
                ->setParameter('employeeName', '%'.$filters['employeeName'].'%');
        }
        if (!empty($filters['department'])) {
            $qb->andWhere('e.department = :department')
                ->setParameter('department', $filters['department']);
        }

        // Sorting
        $qb->orderBy($sort['field'] ?? 'e.name', $sort['order'] ?? 'ASC');

        // Pagination
        $qb->setFirstResult($offset)
            ->setMaxResults($limit);

        return $qb->getQuery()->getArrayResult();
    }

    public function getTotalEmployees(array $filters): int
    {
        $qb = $this->createQueryBuilder(EmployeePayrollOverviewModel::class, 'e')
            ->select('COUNT(e.empNumber)');

        if (!empty($filters['employeeName'])) {
            $qb->andWhere($qb->expr()->like('e.name', ':employeeName'))
                ->setParameter('employeeName', '%'.$filters['employeeName'].'%');
        }
        if (!empty($filters['department'])) {
            $qb->andWhere('e.department = :department')
                ->setParameter('department', $filters['department']);
        }

        return (int)$qb->getQuery()->getSingleScalarResult();
    }
}
