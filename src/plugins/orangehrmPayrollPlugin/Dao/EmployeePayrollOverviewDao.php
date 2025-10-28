<?php

namespace OrangeHRM\Payroll\Dao;

use OrangeHRM\Core\Dao\BaseDao;
use OrangeHRM\Payroll\Api\Model\EmployeePayrollOverviewModel;

class EmployeePayrollOverviewDao extends BaseDao
{
    public function getEmployees(array $filters, int $offset, int $limit, array $sort): array
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->select(
            'e.emp_number',
            "CONCAT(e.emp_firstname, ' ', e.emp_lastname) AS name",
            'b.ebsal_basic_salary AS salary',
            'b.salary_component',
            'd.dd_amount AS direct_debit',
            's.name AS department'
        )
            ->from('hs_hr_employee', 'e')
            ->leftJoin('e', 'hs_hr_emp_basicsalary', 'b', 'b.emp_number = e.emp_number')
            ->leftJoin('b', 'hs_hr_emp_directdebit', 'd', 'd.salary_id = b.id')
            ->leftJoin('e', 'ohrm_subunit', 's', 's.id = e.work_station')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        // Filtering
        if (!empty($filters['employeeName'])) {
            $qb->andWhere("CONCAT(e.emp_firstname, ' ', e.emp_lastname) LIKE :employeeName")
                ->setParameter('employeeName', '%'.$filters['employeeName'].'%');
        }

        if (!empty($filters['department'])) {
            $qb->andWhere('s.id = :department')
                ->setParameter('department', $filters['department']);
        }

        // Sorting — map field names to DB expressions
        $validSortFields = [
            'name' => "e.emp_firstname", // or CONCAT if you prefer
            'salary' => 'b.ebsal_basic_salary',
            'direct_debit' => 'd.dd_amount',
            'department' => 's.name',
        ];

        if (!empty($sort['field']) && isset($validSortFields[$sort['field']])) {
            $qb->orderBy($validSortFields[$sort['field']], strtoupper($sort['order'] ?? 'ASC'));
        } else {
            $qb->orderBy('e.emp_lastname', 'ASC');
        }

        $rows = $qb->executeQuery()->fetchAllAssociative();

        // Map rows to model/DTO
        return array_map(fn($row) => [
            'empNumber' => (int)$row['emp_number'],
            'name' => $row['name'],
            'salary' => isset($row['salary']) ? (float)$row['salary'] : null,
            'salaryComponent' => $row['salary_component'] ?? null,
            'directDebit' => isset($row['direct_debit']) ? (float)$row['direct_debit'] : null,
            'department' => $row['department'] ?? null,
        ], $rows);
    }


    public function getTotalEmployees(array $filters): int
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->select('COUNT(e.emp_number)')
            ->from('hs_hr_employee', 'e')
            ->leftJoin('e', 'ohrm_subunit', 's', 's.id = e.work_station');

        if (!empty($filters['employeeName'])) {
            $qb->andWhere("CONCAT(e.emp_firstname, ' ', e.emp_lastname) LIKE :employeeName")
                ->setParameter('employeeName', '%' . $filters['employeeName'] . '%');
        }

        if (!empty($filters['department'])) {
            $qb->andWhere('s.id = :department')
                ->setParameter('department', $filters['department']);
        }

        // Execute the query safely and return count
        return (int) $qb->executeQuery()->fetchOne();
    }

}
