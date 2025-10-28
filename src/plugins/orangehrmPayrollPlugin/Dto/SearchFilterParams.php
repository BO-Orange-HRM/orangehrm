<?php

namespace OrangeHRM\Payroll\Dto;

use OrangeHRM\Core\Dto\FilterParams;

/**
 * Generic search filter params for payroll-related listings.
 *
 * Common filters: page, limit, sort, order, status, date range, empNumber, componentId, calendarId.
 */
class SearchFilterParams extends FilterParams
{
    public const ALLOWED_SORT_FIELDS = [
        'createdAt',
        'updatedAt',
        'status',
        'empNumber',
        'componentId',
        'calendarId',
        'periodStart',
        'periodEnd',
    ];
    const ORDER_DESC = "ORDER_DESC";

    /**
     * @var int|null
     */
    private ?int $empNumber = null;

    /**
     * @var int|null
     */
    private ?int $componentId = null;

    /**
     * @var int|null
     */
    private ?int $calendarId = null;

    /**
     * @var string|null
     */
    private ?string $status = null;

    /**
     * @var \DateTimeInterface|null
     */
    private ?\DateTimeInterface $fromDate = null;

    /**
     * @var \DateTimeInterface|null
     */
    private ?\DateTimeInterface $toDate = null;

    public function __construct()
    {
        // Defaults
        $this->setLimit(50);
        $this->setPage(1);
        $this->setSortField('createdAt');
        $this->setSortOrder(self::ORDER_DESC);
    }

    // empNumber
    public function getEmpNumber(): ?int
    {
        return $this->empNumber;
    }

    public function setEmpNumber(?int $empNumber): void
    {
        $this->empNumber = $empNumber;
    }

    // componentId
    public function getComponentId(): ?int
    {
        return $this->componentId;
    }

    public function setComponentId(?int $componentId): void
    {
        $this->componentId = $componentId;
    }

    // calendarId
    public function getCalendarId(): ?int
    {
        return $this->calendarId;
    }

    public function setCalendarId(?int $calendarId): void
    {
        $this->calendarId = $calendarId;
    }

    // status
    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    // fromDate
    public function getFromDate(): ?\DateTimeInterface
    {
        return $this->fromDate;
    }

    public function setFromDate(?\DateTimeInterface $fromDate): void
    {
        $this->fromDate = $fromDate;
    }

    // toDate
    public function getToDate(): ?\DateTimeInterface
    {
        return $this->toDate;
    }

    public function setToDate(?\DateTimeInterface $toDate): void
    {
        $this->toDate = $toDate;
    }

    /**
     * Validate and normalize sort field against whitelist.
     * Falls back to default if invalid.
     */
    public function setSortField(?string $sortField): void
    {
        if ($sortField === null) {
            parent::setSortField('createdAt');
            return;
        }
        if (!in_array($sortField, self::ALLOWED_SORT_FIELDS, true)) {
            // Fallback to a safe default
            parent::setSortField('createdAt');
            return;
        }
        parent::setSortField($sortField);
    }
}