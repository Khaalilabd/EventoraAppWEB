<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ReservationPersonaliseServiceRepository;

#[ORM\Entity(repositoryClass: ReservationPersonaliseServiceRepository::class)]
#[ORM\Table(name: 'reservation_personalise_service')]
#[ORM\UniqueConstraint(columns: ['reservation_id', 'service_id'])]
class ReservationPersonaliseService
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Reservationpersonnalise::class, inversedBy: 'reservationServices')]
    #[ORM\JoinColumn(name: 'reservation_id', referencedColumnName: 'IDReservationPersonalise')]
    private ?Reservationpersonnalise $reservation = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: GService::class)]
    #[ORM\JoinColumn(name: 'service_id', referencedColumnName: 'id')]
    private ?GService $service = null;

    public function getReservation(): ?Reservationpersonnalise
    {
        return $this->reservation;
    }

    public function setReservation(?Reservationpersonnalise $reservation): self
    {
        $this->reservation = $reservation;
        return $this;
    }

    public function getService(): ?GService
    {
        return $this->service;
    }

    public function setService(?GService $service): self
    {
        $this->service = $service;
        return $this;
    }
}