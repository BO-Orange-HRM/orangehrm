<?php

namespace OrangeHRM\Payroll\Api\Model;

use OrangeHRM\Core\Api\V2\Serializer\Normalizable;

class PayrollPeriodDetailsModel implements Normalizable
{
    private int $id;
    private array $period;
    private array $overview;
    private array $employees;
    private array $auditLogs;

    public function __construct(
        int $id,
        array $period,
        array $overview,
        array $employees,
        array $auditLogs
    ) {
        $this->id = $id;
        $this->period = $period;
        $this->overview = $overview;
        $this->employees = $employees;
        $this->auditLogs = $auditLogs;
    }

    public function normalize(): array
    {
        return [
            'id' => $this->id,
            'period' => $this->period,
            'overview' => $this->overview,
            'employees' => $this->employees,
            'auditLogs' => $this->auditLogs,
        ];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'period' => $this->period,
            'overview' => $this->overview,
            'employees' => $this->employees,
            'auditLogs' => $this->auditLogs,
        ];
    }
}
