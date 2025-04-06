<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ReservationpackRepository;

#[ORM\Entity(repositoryClass: ReservationpackRepository::class)]
#[ORM\Table(name: 'reservationpack')]
class Reservationpack
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $IDReservationPack = null;
    
    public function getIDReservationPack(): ?int
    {
        return $this->IDReservationPack;
    }

    public function setIDReservationPack(int $IDReservationPack): self
    {
        $this->IDReservationPack = $IDReservationPack;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Pack::class, inversedBy: 'reservationpacks')]
    #[ORM\JoinColumn(name: 'IDPack', referencedColumnName: 'id')]
    private ?Pack $pack = null;

    public function getPack(): ?Pack
    {
        return $this->pack;
    }

    public function setPack(?Pack $pack): self
    {
        $this->pack = $pack;
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
