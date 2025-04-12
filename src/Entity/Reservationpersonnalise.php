<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\ReservationpersonnaliseRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReservationpersonnaliseRepository::class)]
#[ORM\Table(name: 'reservationpersonnalise')]
class Reservationpersonnalise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', name: 'IDReservationPersonalise')]
    private ?int $IDReservationPersonalise = null;

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: "Le nom est requis.")]
    private ?string $Nom = null;

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: "Le prénom est requis.")]
    private ?string $Prenom = null;

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: "L'email est requis.")]
    #[Assert\Email(message: "L'email n'est pas valide.")]
    private ?string $Email = null;

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: "Le numéro de téléphone est requis.")]
    #[Assert\Regex(
        pattern: "/^\+?[1-9]\d{1,14}$/",
        message: "Le numéro de téléphone n'est pas valide."
    )]
    private ?string $Numtel = null;

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: "La description est requise.")]
    private ?string $Description = null;

    #[ORM\Column(type: 'date', nullable: false)]
    #[Assert\NotBlank(message: "La date est requise.")]
    #[Assert\GreaterThanOrEqual("today", message: "La date doit être aujourd'hui ou dans le futur.")]
    private ?\DateTimeInterface $Date = null;

    #[ORM\ManyToMany(targetEntity: GService::class, inversedBy: 'reservations')]
    #[ORM\JoinTable(name: 'reservation_personalise_service')]
    #[ORM\JoinColumn(name: 'reservation_id', referencedColumnName: 'IDReservationPersonalise')]
    #[ORM\InverseJoinColumn(name: 'service_id', referencedColumnName: 'id')]
    #[Assert\Count(
        min: 1,
        minMessage: "Vous devez sélectionner au moins un service."
    )]
    private Collection $services;

    public function __construct()
    {
        $this->services = new ArrayCollection();
    }

    public function getIDReservationPersonalise(): ?int
    {
        return $this->IDReservationPersonalise;
    }

    public function setIDReservationPersonalise(int $IDReservationPersonalise): self
    {
        $this->IDReservationPersonalise = $IDReservationPersonalise;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->Nom;
    }

    public function setNom(string $Nom): self
    {
        $this->Nom = $Nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->Prenom;
    }

    public function setPrenom(string $Prenom): self
    {
        $this->Prenom = $Prenom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->Email;
    }

    public function setEmail(string $Email): self
    {
        $this->Email = $Email;
        return $this;
    }

    public function getNumtel(): ?string
    {
        return $this->Numtel;
    }

    public function setNumtel(string $Numtel): self
    {
        $this->Numtel = $Numtel;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->Description;
    }

    public function setDescription(string $Description): self
    {
        $this->Description = $Description;
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->Date;
    }

    public function setDate(\DateTimeInterface $Date): self
    {
        $this->Date = $Date;
        return $this;
    }

    /**
     * @return Collection|GService[]
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(GService $service): self
    {
        if (!$this->services->contains($service)) {
            $this->services->add($service);
            $service->addReservation($this);
        }
        return $this;
    }

    public function removeService(GService $service): self
    {
        if ($this->services->removeElement($service)) {
            $service->removeReservation($this);
        }
        return $this;
    }
}