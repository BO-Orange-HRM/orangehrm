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
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @var Employee
     *
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\Employee")
     * @ORM\JoinColumn(name="employee_number", referencedColumnName="emp_number")
     */
    private Employee $employee;

    /**
     * @var float
     *
     * @ORM\Column(name="basic_salary", type="decimal", precision=15, scale=2)
     */
    private float $basicSalary;

    /**
     * @var float
     *
     * @ORM\Column(name="allowances", type="decimal", precision=15, scale=2, nullable=true)
     */
    private ?float $allowances = null;

    /**
     * @var float
     *
     * @ORM\Column(name="deductions", type="decimal", precision=15, scale=2, nullable=true)
     */
    private ?float $deductions = null;

    /**
     * @var float
     *
     * @ORM\Column(name="net_salary", type="decimal", precision=15, scale=2)
     */
    private float $netSalary;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3)
     */
    private string $currency = 'USD';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="payment_date", type="date")
     */
    private \DateTime $paymentDate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="status", type="string", length=20)
     */
    private ?string $status = 'pending';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private \DateTime $createdAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;
    }

    public function getBasicSalary(): float
    {
        return $this->basicSalary;
    }

    public function setBasicSalary(float $basicSalary): void
    {
        $this->basicSalary = $basicSalary;
    }

    public function getAllowances(): ?float
    {
        return $this->allowances;
    }

    public function setAllowances(?float $allowances): void
    {
        $this->allowances = $allowances;
    }

    public function getDeductions(): ?float
    {
        return $this->deductions;
    }

    public function setDeductions(?float $deductions): void
    {
        $this->deductions = $deductions;
    }

    public function getNetSalary(): float
    {
        return $this->netSalary;
    }

    public function setNetSalary(float $netSalary): void
    {
        $this->netSalary = $netSalary;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getPaymentDate(): \DateTime
    {
        return $this->paymentDate;
    }

    public function setPaymentDate(\DateTime $paymentDate): void
    {
        $this->paymentDate = $paymentDate;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
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
}