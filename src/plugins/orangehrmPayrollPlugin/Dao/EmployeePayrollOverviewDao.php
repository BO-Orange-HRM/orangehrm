<?php

namespace OrangeHRM\Payroll\Dao;

use Doctrine\DBAL\Exception;
use OrangeHRM\Core\Dao\BaseDao;

class EmployeePayrollOverviewDao extends BaseDao
{
    /**
     * @throws Exception
     */
    public function getEmployees(array $filters, int $offset, int $limit, array $sort): array
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->select(
            'e.emp_number',
            "CONCAT(e.emp_firstname, ' ', e.emp_lastname) AS name",
            'jc.name AS job_category',
            's.name AS sub_unit',
            'es.name AS employment_status',
            'SUM(COALESCE(b.ebsal_basic_salary, 0)) AS gross_salary',
            "GROUP_CONCAT(DISTINCT b.salary_component ORDER BY b.salary_component SEPARATOR ', ') AS salary_components"
        )
            ->from('hs_hr_employee', 'e')
            ->leftJoin('e', 'hs_hr_emp_basicsalary', 'b', 'b.emp_number = e.emp_number')
            ->leftJoin('e', 'ohrm_subunit', 's', 's.id = e.work_station')
            ->leftJoin('e', 'ohrm_job_category', 'jc', 'e.eeo_cat_code = jc.id')
            ->leftJoin('e', 'ohrm_employment_status', 'es', 'es.id = e.emp_status')
            ->groupBy('e.emp_number', 's.name', 'jc.name', 'es.name')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        // 🔍 Filtering
        if (!empty($filters['employeeName'])) {
            $qb->andWhere("CONCAT(e.emp_firstname, ' ', e.emp_lastname) LIKE :employeeName")
                ->setParameter('employeeName', '%'.$filters['employeeName'].'%');
        }

        if (!empty($filters['department'])) {
            $qb->andWhere('s.id = :department')
                ->setParameter('department', $filters['department']);
        }

        // 🔢 Sorting
        $validSortFields = [
            'name' => 'e.emp_firstname',
            'components' => 'b.salary_component',
            'grossSalary' => 'gross_salary',
        ];

        if (!empty($sort['field']) && isset($validSortFields[$sort['field']])) {
            $qb->orderBy($validSortFields[$sort['field']], strtoupper($sort['order'] ?? 'ASC'));
        } else {
            $qb->orderBy('e.emp_lastname', 'ASC');
        }

        $rows = $qb->executeQuery()->fetchAllAssociative();

        // ✅ Return clean data
        return array_map(fn($row) => [
            'empNumber' => (int)$row['emp_number'],
            'name' => $row['name'],
            'jobCategory' => $row['job_category'] ?? null,
            'subUnit' => $row['sub_unit'] ?? null,
            'employmentStatus' => $row['employment_status'] ?? null,
            'components' => $row['salary_components'] ?? '',
            'grossSalary' => isset($row['gross_salary']) ? (float)$row['gross_salary'] : 0,
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
        return (int)$qb->executeQuery()->fetchOne();
    }

}
