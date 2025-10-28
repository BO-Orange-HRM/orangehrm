<?php

namespace OrangeHRM\Payroll\Dao;

use OrangeHRM\Core\Dao\BaseDao;
use OrangeHRM\Entity\Payroll;
use OrangeHRM\Entity\PayrollComponent;
use OrangeHRM\ORM\Paginator;
use OrangeHRM\Payroll\Dto\PayrollSearchFilterParams;

class PayrollDao extends BaseDao
{
    /**
     * @param Payroll $payroll
     * @return Payroll
     */
    public function savePayroll(Payroll $payroll): Payroll
    {
        $this->persist($payroll);
        return $payroll;
    }

    /**
     * @param int $id
     * @return Payroll|null
     */
    public function getPayrollById(int $id): ?Payroll
    {
        return $this->getRepository(Payroll::class)->find($id);
    }

    /**
     * @param PayrollSearchFilterParams $payrollSearchFilterParams
     * @return array
     */
    public function getPayrollList(PayrollSearchFilterParams $payrollSearchFilterParams): array
    {
        $qb = $this->getPayrollListQueryBuilder($payrollSearchFilterParams);
        return $qb->getQuery()->execute();
    }

    /**
     * @param PayrollSearchFilterParams $payrollSearchFilterParams
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getPayrollListQueryBuilder(PayrollSearchFilterParams $payrollSearchFilterParams)
    {
        $qb = $this->createQueryBuilder(Payroll::class, 'p');
        $qb->leftJoin('p.employee', 'e');

        if (!is_null($payrollSearchFilterParams->getEmployeeNumber())) {
            $qb->andWhere('e.empNumber = :empNumber')
                ->setParameter('empNumber', $payrollSearchFilterParams->getEmployeeNumber());
        }

        if (!is_null($payrollSearchFilterParams->getStatus())) {
            $qb->andWhere('p.status = :status')
                ->setParameter('status', $payrollSearchFilterParams->getStatus());
        }

        if (!is_null($payrollSearchFilterParams->getFromDate())) {
            $qb->andWhere('p.paymentDate >= :fromDate')
                ->setParameter('fromDate', $payrollSearchFilterParams->getFromDate());
        }

        if (!is_null($payrollSearchFilterParams->getToDate())) {
            $qb->andWhere('p.paymentDate <= :toDate')
                ->setParameter('toDate', $payrollSearchFilterParams->getToDate());
        }

        $qb->addOrderBy('p.paymentDate', 'DESC');

        return $qb;
    }

    /**
     * @param PayrollSearchFilterParams $payrollSearchFilterParams
     * @return int
     */
    public function getPayrollCount(PayrollSearchFilterParams $payrollSearchFilterParams): int
    {
        $qb = $this->getPayrollListQueryBuilder($payrollSearchFilterParams);
        return $this->getPaginator($qb)->count();
    }

    /**
     * @param int $id
     * @return bool
     */
    public function deletePayroll(int $id): bool
    {
        $payroll = $this->getPayrollById($id);
        if ($payroll) {
            $this->remove($payroll);
            return true;
        }
        return false;
    }

    /**
     * @param PayrollComponent $component
     * @return PayrollComponent
     */
    public function savePayrollComponent(PayrollComponent $component): PayrollComponent
    {
        $this->persist($component);
        return $component;
    }

    /**
     * @return array
     */
    public function getPayrollComponents(): array
    {
        return $this->getRepository(PayrollComponent::class)->findBy(['isActive' => true]);
    }
}