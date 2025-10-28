<?php


namespace OrangeHRM\Payroll\Api\Model;

use OrangeHRM\Core\Api\V2\Serializer\ModelTrait;
use OrangeHRM\Core\Api\V2\Serializer\Normalizable;

class PayrollDashboardModel  implements Normalizable
{
    use ModelTrait;
    /**
     * @var array|null
     */
    protected ?array $activePeriod = null;

    /**
     * @var array|null
     */
    protected ?array $kpis = null;

    /**
     * @var array|null
     */
    protected ?array $alerts = null;

    /**
     * @var array
     */
    protected array $recentPeriods = [];

    // Add required getters and setters (boilerplate)
    // The serialization process usually requires getters for the properties.

    public function getActivePeriod(): ?array
    {
        return $this->activePeriod;
    }

    public function setActivePeriod(?array $activePeriod): void
    {
        $this->activePeriod = $activePeriod;
    }

    public function getKpis(): ?array
    {
        return $this->kpis;
    }

    public function setKpis(?array $kpis): void
    {
        $this->kpis = $kpis;
    }

    public function getAlerts(): ?array
    {
        return $this->alerts;
    }

    public function setAlerts(?array $alerts): void
    {
        $this->alerts = $alerts;
    }

    public function getRecentPeriods(): array
    {
        return $this->recentPeriods;
    }

    public function setRecentPeriods(array $recentPeriods): void
    {
        $this->recentPeriods = $recentPeriods;
    }

    public function toArray(): array
    {
        // TODO: Implement toArray() method.
        return [];
    }
}