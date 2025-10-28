<?php

namespace OrangeHRM\Payroll\Api\Model;

use OrangeHRM\Core\Api\V2\Serializer\ModelConstructorArgsAwareInterface;
use OrangeHRM\Core\Api\V2\Serializer\Normalizable;

class EmployeePayrollOverviewModel implements Normalizable, ModelConstructorArgsAwareInterface
{
    private int $empNumber;
    private string $name;
    private ?float $salary;
    private ?string $salaryComponent;
    private ?float $directDebit;
    private ?string $department;

    public function __construct(
        int $empNumber,
        string $name,
        ?float $salary = null,
        ?string $salaryComponent = null,
        ?float $directDebit = null,
        ?string $department = null
    ) {
        $this->empNumber = $empNumber;
        $this->name = $name;
        $this->salary = $salary;
        $this->salaryComponent = $salaryComponent;
        $this->directDebit = $directDebit;
        $this->department = $department;
    }

    // Getters
    public function getEmpNumber(): int
    {
        return $this->empNumber;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSalary(): ?float
    {
        return $this->salary;
    }

    public function getSalaryComponent(): ?string
    {
        return $this->salaryComponent;
    }

    public function getDirectDebit(): ?float
    {
        return $this->directDebit;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    // Convert to array for API serialization
    public function toArray(): array
    {
        return [
            'empNumber' => $this->empNumber,
            'name' => $this->name,
            'salary' => $this->salary,
            'salaryComponent' => $this->salaryComponent,
            'directDebit' => $this->directDebit,
            'department' => $this->department,
        ];
    }
}
