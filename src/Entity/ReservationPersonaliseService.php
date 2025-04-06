<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ReservationPersonaliseServiceRepository;

#[ORM\Entity(repositoryClass: ReservationPersonaliseServiceRepository::class)]
#[ORM\Table(name: 'reservation_personalise_service')]
class ReservationPersonaliseService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', name: 'IDReservationPersonalise ' )]
    private ?int $reservation_id = null;

    public function getReservation_id(): ?int
    {
        return $this->reservation_id;
    }

    public function setReservation_id(int $reservation_id): self
    {
        $this->reservation_id = $reservation_id;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $service_id = null;

    public function getService_id(): ?int
    {
        return $this->service_id;
    }

    public function setService_id(int $service_id): self
    {
        $this->service_id = $service_id;
        return $this;
    }

    public function getReservationId(): ?int
    {
        return $this->reservation_id;
    }

    public function getServiceId(): ?int
    {
        return $this->service_id;
    }

    public function setServiceId(int $service_id): static
    {
        $this->service_id = $service_id;

        return $this;
    }
    
    #[ORM\OneToMany(targetEntity: ReservationPersonaliseService::class, mappedBy: 'reservation_id')]
private Collection $services;

public function __construct()
{
    $this->services = new ArrayCollection();
}

public function getServices(): Collection
{
    return $this->services;
}

public function addService(ReservationPersonaliseService $service): self
{
    if (!$this->services->contains($service)) {
        $this->services->add($service);
        $service->setReservationId($this->getIDReservationPersonalise());
    }
    return $this;
}

public function removeService(ReservationPersonaliseService $service): self
{
    if ($this->services->removeElement($service)) {
        if ($service->getReservationId() === $this->getIDReservationPersonalise()) {
            $service->setReservationId(null);
        }
    }
    return $this;
}

}
