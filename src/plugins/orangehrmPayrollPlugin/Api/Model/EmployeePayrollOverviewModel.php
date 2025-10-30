<?php

namespace OrangeHRM\Payroll\Api\Model;

use OrangeHRM\Core\Api\V2\Serializer\ModelConstructorArgsAwareInterface;
use OrangeHRM\Core\Api\V2\Serializer\Normalizable;

class EmployeePayrollOverviewModel implements Normalizable, ModelConstructorArgsAwareInterface
{
    private int $empNumber;
    private string $name;
    private ?string $jobCategory;
    private ?string $subUnit;
    private ?string $employmentStatus;
    private ?string $components;
    private ?float $grossSalary;

    public function __construct(
        int $empNumber,
        string $name,
        ?string $jobCategory = null,
        ?string $subUnit = null,
        ?string $employmentStatus = null,
        ?string $components = null,
        ?float $grossSalary = null
    ) {
        $this->empNumber = $empNumber;
        $this->name = $name;
        $this->jobCategory = $jobCategory;
        $this->subUnit = $subUnit;
        $this->employmentStatus = $employmentStatus;
        $this->components = $components;
        $this->grossSalary = $grossSalary;
    }

    // Getters
    public function getEmpNumber(): int { return $this->empNumber; }
    public function getName(): string { return $this->name; }
    public function getJobCategory(): ?string { return $this->jobCategory; }
    public function getSubUnit(): ?string { return $this->subUnit; }
    public function getEmploymentStatus(): ?string { return $this->employmentStatus; }
    public function getComponents(): ?string { return $this->components; }
    public function getGrossSalary(): ?float { return $this->grossSalary; }

    public function toArray(): array
    {
        return [
            'empNumber' => $this->empNumber,
            'name' => $this->name,
            'jobCategory' => $this->jobCategory,
            'subUnit' => $this->subUnit,
            'employmentStatus' => $this->employmentStatus,
            'components' => $this->components,
            'grossSalary' => $this->grossSalary,
        ];
    }
}
