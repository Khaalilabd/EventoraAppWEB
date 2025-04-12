<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\GServiceRepository;

#[ORM\Entity(repositoryClass: GServiceRepository::class)]
#[ORM\Table(name: 'g_service')]
class GService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Sponsor::class, inversedBy: 'gServices')]
    #[ORM\JoinColumn(name: 'id_partenaire', referencedColumnName: 'id_partenaire')]
    private ?Sponsor $sponsor = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $titre = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $location = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $type_service = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $prix = null;

    #[ORM\ManyToMany(targetEntity: Reservationpersonnalise::class, mappedBy: 'services')]
    private Collection $reservations;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getSponsor(): ?Sponsor
    {
        return $this->sponsor;
    }

    public function setSponsor(?Sponsor $sponsor): self
    {
        $this->sponsor = $sponsor;
        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getTypeService(): ?string
    {
        return $this->type_service;
    }

    public function setTypeService(string $type_service): self
    {
        $this->type_service = $type_service;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): self
    {
        $this->prix = $prix;
        return $this;
    }

    /**
     * @return Collection|Reservationpersonnalise[]
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservationpersonnalise $reservation): self
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
        }
        return $this;
    }

    public function removeReservation(Reservationpersonnalise $reservation): self
    {
        $this->reservations->removeElement($reservation);
        return $this;
    }
}