<?php

namespace App\Entity;

use App\Repository\PaymentHistoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PaymentHistoryRepository::class)]
class PaymentHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $transactionId = null;

    #[ORM\Column]
    private ?float $amount = null;

    #[ORM\Column(length: 50)]
    private ?string $currency = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $paymentMethod = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'paymentHistories')]
    private ?Membre $membre = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reservationType = null;

    #[ORM\Column(nullable: true)]
    private ?int $reservationId = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private array $details = [];

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setTransactionId(string $transactionId): self
    {
        $this->transactionId = $transactionId;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getMembre(): ?Membre
    {
        return $this->membre;
    }

    public function setMembre(?Membre $membre): self
    {
        $this->membre = $membre;

        return $this;
    }

    public function getReservationType(): ?string
    {
        return $this->reservationType;
    }

    public function setReservationType(?string $reservationType): self
    {
        $this->reservationType = $reservationType;

        return $this;
    }

    public function getReservationId(): ?int
    {
        return $this->reservationId;
    }

    public function setReservationId(?int $reservationId): self
    {
        $this->reservationId = $reservationId;

        return $this;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function setDetails(?array $details): self
    {
        $this->details = $details ?: [];

        return $this;
    }
}
