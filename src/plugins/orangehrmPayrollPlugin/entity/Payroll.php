<?php

namespace OrangeHRM\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="ohrm_payroll")
 * @ORM\Entity
 */
class Payroll
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\Column(name="pay_period_id", type="integer", nullable=true)
     */
    private ?int $payrollPeriodId = null;

    /**
     * @ORM\Column(name="employee_id", type="integer")
     */
    private int $employeeId;

    /**
     * @ORM\Column(name="gross_amount", type="decimal", precision=15, scale=2)
     */
    private float $grossAmount = 0.00;

    /**
     * @ORM\Column(name="deductions", type="decimal", precision=15, scale=2)
     */
    private float $deductions = 0.00;

    /**
     * @ORM\Column(name="net_amount", type="decimal", precision=15, scale=2)
     */
    private float $netAmount = 0.00;

    /**
     * @ORM\Column(name="status", type="string", length=20)
     */
    private string $status = 'pending';

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    private \DateTime $createdAt;

    /**
     * @var \DateTime $updatedAt
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private \DateTime $updatedAt;

    // Getters and Setters
    public function getId(): int
    {
        return $this->id;
    }

    public function getPayrollPeriodId(): ?int
    {
        return $this->payrollPeriodId;
    }

    public function setPayrollPeriodId(?int $payrollPeriodId): void
    {
        $this->payrollPeriodId = $payrollPeriodId;
    }

    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    public function setEmployeeId(int $employeeId): void
    {
        $this->employeeId = $employeeId;
    }

    public function getGrossAmount(): float
    {
        return $this->grossAmount;
    }

    public function setGrossAmount(float $grossAmount): void
    {
        $this->grossAmount = $grossAmount;
    }

    public function getDeductions(): float
    {
        return $this->deductions;
    }

    public function setDeductions(float $deductions): void
    {
        $this->deductions = $deductions;
    }

    public function getNetAmount(): float
    {
        return $this->netAmount;
    }

    public function setNetAmount(float $netAmount): void
    {
        $this->netAmount = $netAmount;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }
    public function setUpdatedAt(\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}