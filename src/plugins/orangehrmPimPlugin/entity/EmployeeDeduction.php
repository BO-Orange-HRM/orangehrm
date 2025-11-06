<?php
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 */

namespace OrangeHRM\Entity;

use Doctrine\ORM\Mapping as ORM;
use OrangeHRM\Entity\Decorator\DecoratorTrait;
use OrangeHRM\Entity\Decorator\EmployeeDeductionDecorator;

/**
 * @method EmployeeDeductionDecorator getDecorator()
 *
 * @ORM\Table(name="hs_hr_emp_deduction")
 * @ORM\Entity
 */
class EmployeeDeduction
{
    use DecoratorTrait;

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
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\Employee", inversedBy="deductions")
     * @ORM\JoinColumn(name="emp_number", referencedColumnName="emp_number", nullable=false)
     */
    private Employee $employee;

    /**
     * @var string
     *
     * @ORM\Column(name="deduction_name", type="string", length=100)
     */
    private string $name;

    /**
     * @var string
     *
     * @ORM\Column(name="amount", type="decimal", precision=15, scale=2)
     */
    private string $amount;

    /**
     * @var string|null
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    private ?string $comment = null;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="effective_date", type="date")
     */
    private \DateTime $effectiveDate;

    // Getters and Setters
    public function getId(): int
    {
        return $this->id;
    }

    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    public function setEmployee(Employee $employee): self
    {
        $this->employee = $employee;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function getEffectiveDate(): \DateTime
    {
        return $this->effectiveDate;
    }

    public function setEffectiveDate(\DateTime $effectiveDate): self
    {
        $this->effectiveDate = $effectiveDate;
        return $this;
    }
}
