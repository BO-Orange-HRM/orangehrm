<?php

namespace OrangeHRM\Entity;

use Doctrine\ORM\Mapping as ORM;
use OrangeHRM\Core\Api\V2\Serializer\Normalizable;

/**
 * @ORM\Table(name="ohrm_payroll_period")
 * @ORM\Entity
 */
class PayrollPeriod
{
    /**
     * @var int
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;
    
    /**
     * @var string
     * @ORM\Column(name="name", type="string", length=255)
     */
    private string $name;

    /**
     * @var \DateTime
     * @ORM\Column(name="start_date", type="date")
     */
    private \DateTime $startDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="end_date", type="date")
     */
    private \DateTime $endDate;

    /**
     * @var string
     * @ORM\Column(name="status", type="string", length=20)
     */
    private string $status = 'draft'; // draft, open, processed, approved

    /**
     * @var float|null
     * @ORM\Column(name="total_amount", type="decimal", precision=15, scale=2, nullable=true)
     */
    private ?float $totalAmount = null;

    /**
     * @var \DateTime
     * @ORM\Column(name="created_at", type="datetime")
     */
    private \DateTime $createdAt;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="processed_at", type="datetime", nullable=true)
     */
    private ?\DateTime $processedAt = null;
    /**
     * @var mixed
     */
    private $frequency;
    private \DateTime $paymentDate;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTime $startDate): void
    {
        $this->startDate = $startDate;
    }

    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTime $endDate): void
    {
        $this->endDate = $endDate;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getTotalAmount(): ?float
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(?float $totalAmount): void
    {
        $this->totalAmount = $totalAmount;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getProcessedAt(): ?\DateTime
    {
        return $this->processedAt;
    }

    public function setProcessedAt(?\DateTime $processedAt): void
    {
        $this->processedAt = $processedAt;
    }

    public function setFrequency($frequency)
    {
        $this->frequency = $frequency;
    }

    public function getFrequency()
    {
        return $this->frequency;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPaymentDate(): \DateTime
    {
        return $this->paymentDate;
    }

    public function setPaymentDate(\DateTime $paymentDate): void
    {
        $this->paymentDate = $paymentDate;
    }
}