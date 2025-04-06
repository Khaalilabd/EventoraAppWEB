<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ReservationpersonnaliseRepository;

#[ORM\Entity(repositoryClass: ReservationpersonnaliseRepository::class)]
#[ORM\Table(name: 'reservationpersonnalise')]
class Reservationpersonnalise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer',  name: 'IDReservationPersonalise')]
    private ?int $IDReservationPersonalise = null;

    public function getIDReservationPersonalise(): ?int
    {
        return $this->IDReservationPersonalise;
    }

    public function setIDReservationPersonalise(int $IDReservationPersonalise): self
    {
        $this->IDReservationPersonalise = $IDReservationPersonalise;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $Nom = null;

    public function getNom(): ?string
    {
        return $this->Nom;
    }

    public function setNom(string $Nom): self
    {
        $this->Nom = $Nom;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $Prenom = null;

    public function getPrenom(): ?string
    {
        return $this->Prenom;
    }

    public function setPrenom(string $Prenom): self
    {
        $this->Prenom = $Prenom;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $Email = null;

    public function getEmail(): ?string
    {
        return $this->Email;
    }

    public function setEmail(string $Email): self
    {
        $this->Email = $Email;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $Numtel = null;

    public function getNumtel(): ?string
    {
        return $this->Numtel;
    }

    public function setNumtel(string $Numtel): self
    {
        $this->Numtel = $Numtel;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $Description = null;

    public function getDescription(): ?string
    {
        return $this->Description;
    }

    public function setDescription(string $Description): self
    {
        $this->Description = $Description;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $Date = null;

    public function getDate(): ?\DateTimeInterface
    {
        return $this->Date;
    }

    public function setDate(\DateTimeInterface $Date): self
    {
        $this->Date = $Date;
        return $this;
    }

}
