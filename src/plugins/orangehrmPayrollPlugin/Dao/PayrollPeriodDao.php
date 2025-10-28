<?php

namespace OrangeHRM\Payroll\Dao;

use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\TransactionRequiredException;
use OrangeHRM\Core\Dao\BaseDao;
use OrangeHRM\Entity\PayrollPeriod;
use Doctrine\ORM\EntityManager;

class PayrollPeriodDao extends BaseDao
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function save(PayrollPeriod $period): PayrollPeriod
    {
        $this->entityManager->persist($period);
        $this->entityManager->flush();
        return $period;
    }

    /**
     * @throws NotSupported
     */
    public function getAll(): array
    {
        return $this->entityManager->getRepository(PayrollPeriod::class)->findAll();
    }

    /**
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     * @throws ORMException
     */
    public function findById(int $id): ?PayrollPeriod
    {
        return $this->entityManager->find(PayrollPeriod::class, $id);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function delete(PayrollPeriod $period): void
    {
        $this->entityManager->remove($period);
        $this->entityManager->flush();
    }

    /**
     * Get the currently active payroll period
     * @return PayrollPeriod|null
     */
    public function getActivePeriod(): ?PayrollPeriod
    {
        $qb = $this->createQueryBuilder(PayrollPeriod::class, 'pp');
        $qb->where('pp.status IN (:statuses)')
            ->setParameter('statuses', ['open', 'processed'])
            ->orderBy('pp.startDate', 'DESC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Get recent payroll periods
     * @param int $limit
     * @return array
     */
    public function getRecentPeriods(int $limit = 5): array
    {
        $qb = $this->createQueryBuilder(PayrollPeriod::class, 'pp');
        $qb->orderBy('pp.startDate', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->execute();
    }

    /**
     * @param PayrollPeriod $period
     * @return PayrollPeriod
     */
    public function savePayrollPeriod(PayrollPeriod $period): PayrollPeriod
    {
        $this->persist($period);
        return $period;
    }

    /**
     * @param int $id
     * @return PayrollPeriod|null
     */
    public function getPayrollPeriodById(int $id): ?PayrollPeriod
    {
        return $this->getRepository(PayrollPeriod::class)->find($id);
    }

    /**
     * Get total employees in a period
     * @param int $periodId
     * @return int
     */
    public function getTotalEmployeesInPeriod(int $periodId): int
    {
        $qb = $this->createQueryBuilder(\OrangeHRM\Entity\Payroll::class, 'p');
        $qb->select('COUNT(DISTINCT p.employee)')
            ->where('p.payrollPeriod = :periodId')
            ->setParameter('periodId', $periodId);

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get total amount for a period
     * @param int $periodId
     * @return float
     */
    public function getTotalAmountForPeriod(int $periodId): float
    {
        $qb = $this->createQueryBuilder(\OrangeHRM\Entity\Payroll::class, 'p');
        $qb->select('SUM(p.netSalary)')
            ->where('p.payrollPeriod = :periodId')
            ->setParameter('periodId', $periodId);

        return (float)($qb->getQuery()->getSingleScalarResult() ?? 0);
    }

    /**
     * Get count of pending approvals
     * @return int
     */
    public function getPendingApprovalsCount(): int
    {
        $qb = $this->createQueryBuilder(\OrangeHRM\Entity\Payroll::class, 'p');
        $qb->select('COUNT(p.id)')
            ->where('p.status = :status')
            ->setParameter('status', 'pending');

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get last payroll run date
     * @return string|null
     */
    public function getLastPayrollRunDate(): ?string
    {
        $qb = $this->createQueryBuilder(PayrollPeriod::class, 'pp');
        $qb->select('pp.processedAt')
            ->where('pp.processedAt IS NOT NULL')
            ->orderBy('pp.processedAt', 'DESC')
            ->setMaxResults(1);

        $result = $qb->getQuery()->getOneOrNullResult();

        return $result && $result['processedAt']
            ? $result['processedAt']->format('Y-m-d')
            : null;
    }
}
