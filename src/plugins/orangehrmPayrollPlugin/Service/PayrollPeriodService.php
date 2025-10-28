<?php

namespace OrangeHRM\Payroll\Service;

use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\TransactionRequiredException;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\PayrollPeriod;
use OrangeHRM\Payroll\Dao\PayrollPeriodDao;
use Exception;

class PayrollPeriodService
{
    use EntityManagerHelperTrait;

    private ?PayrollPeriodDao $payrollPeriodDao = null;

    /**
     * @throws Exception
     */
    public function createPeriod(array $data): PayrollPeriod
    {
        $period = new PayrollPeriod();
        $period->setStartDate(new \DateTime($data['startDate']));
        $period->setEndDate(new \DateTime($data['endDate']));
        $period->setProcessedAt(new \DateTime($data['paymentDate']));
        $period->setFrequency($data['frequency']);
        $period->setStatus('Draft');
        $period->setCreatedAt(new \DateTime());

        if ($period->getStartDate() > $period->getEndDate()) {
            throw new Exception("Start date cannot be after end date.");
        }

        return $this->getPayrollPeriodDao()->save($period);
    }

    /**
     * @throws NotSupported
     */
    public function getAll(): array
    {
        return $this->getPayrollPeriodDao()->getAll();
    }

    /**
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     * @throws ORMException
     */
    public function updateStatus(int $id, string $status): PayrollPeriod
    {
        $period = $this->getPayrollPeriodDao()->findById($id);
        if (!$period) throw new Exception("Payroll period not found.");
        $period->setStatus($status);
        return $this->getPayrollPeriodDao()->save($period);
    }

    /**
     * @return PayrollPeriodDao
     */
    public function getPayrollPeriodDao(): PayrollPeriodDao
    {
        if (!$this->payrollPeriodDao instanceof PayrollPeriodDao) {
            $this->payrollPeriodDao = new PayrollPeriodDao($this->getEntityManager());
        }
        return $this->payrollPeriodDao;
    }

    /**
     * @return PayrollPeriod|null
     */
    public function getActivePeriod(): ?PayrollPeriod
    {
        return $this->getPayrollPeriodDao()->getActivePeriod();
    }

    /**
     * @param int $limit
     * @return array
     */
    public function getRecentPeriods(int $limit = 5): array
    {
        return $this->getPayrollPeriodDao()->getRecentPeriods($limit);
    }

    /**
     * @return int
     */
    public function getTotalEmployeesInActivePeriod(): int
    {
        $activePeriod = $this->getActivePeriod();
        if (!$activePeriod) {
            return 0;
        }
        return $this->getPayrollPeriodDao()->getTotalEmployeesInPeriod($activePeriod->getId());
    }

    /**
     * @return float
     */
    public function getTotalAmountForActivePeriod(): float
    {
        $activePeriod = $this->getActivePeriod();
        if (!$activePeriod) {
            return 0.0;
        }
        return $this->getPayrollPeriodDao()->getTotalAmountForPeriod($activePeriod->getId());
    }

    /**
     * @return int
     */
    public function getPendingApprovalsCount(): int
    {
        return $this->getPayrollPeriodDao()->getPendingApprovalsCount();
    }

    /**
     * @return string|null
     */
    public function getLastPayrollRunDate(): ?string
    {
        return $this->getPayrollPeriodDao()->getLastPayrollRunDate();
    }

    /**
     * Get payroll alerts/warnings
     * @return array
     */
    public function getPayrollAlerts(): array
    {
        $alerts = [];

        // Check if there are pending approvals
        $pendingCount = $this->getPendingApprovalsCount();
        if ($pendingCount > 0) {
            $alerts[] = [
                'message' => "You have {$pendingCount} payroll entries pending approval"
            ];
        }

        // Check if active period is about to end
        $activePeriod = $this->getActivePeriod();
        if ($activePeriod) {
            $daysUntilEnd = (new \DateTime())->diff($activePeriod->getEndDate())->days;
            if ($daysUntilEnd <= 3) {
                $alerts[] = [
                    'message' => "Current payroll period ends in {$daysUntilEnd} days"
                ];
            }
        }

        return $alerts;
    }

    /**
     * @param PayrollPeriod $period
     * @return PayrollPeriod
     */
    public function savePayrollPeriod(PayrollPeriod $period): PayrollPeriod
    {
        if (!$period->getCreatedAt()) {
            $period->setCreatedAt(new \DateTime());
        }
        return $this->getPayrollPeriodDao()->savePayrollPeriod($period);
    }
}
