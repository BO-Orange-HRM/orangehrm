<?php

namespace OrangeHRM\Payroll\Dto;

use OrangeHRM\Core\Dto\FilterParams;

class PayrollSearchFilterParams extends FilterParams
{
    public const ALLOWED_SORT_FIELDS = ['p.paymentDate', 'e.empNumber', 'p.netSalary'];

    private ?int $employeeNumber = null;
    private ?string $status = null;
    private ?\DateTime $fromDate = null;
    private ?\DateTime $toDate = null;

    public function __construct()
    {
        $this->setSortField('p.paymentDate');
    }

    public function getEmployeeNumber(): ?int
    {
        return $this->employeeNumber;
    }

    public function setEmployeeNumber(?int $employeeNumber): void
    {
        $this->employeeNumber = $employeeNumber;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function getFromDate(): ?\DateTime
    {
        return $this->fromDate;
    }

    public function setFromDate(?\DateTime $fromDate): void
    {
        $this->fromDate = $fromDate;
    }

    public function getToDate(): ?\DateTime
    {
        return $this->toDate;
    }

    public function setToDate(?\DateTime $toDate): void
    {
        $this->toDate = $toDate;
    }
}