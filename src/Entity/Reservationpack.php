<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: 'App\Repository\ReservationpackRepository')]
#[ORM\Table(name: 'reservationpack')]
class Reservationpack
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', name: 'IDReservationPack')]
    private ?int $IDReservationPack = null;

    #[ORM\ManyToOne(targetEntity: Pack::class)]
    #[ORM\JoinColumn(name: 'IDPack', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotBlank(message: "Vous devez choisir un pack.")]
    private ?Pack $pack = null;

    #[ORM\ManyToOne(targetEntity: Membre::class)]
    #[ORM\JoinColumn(name: 'idMembre', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotBlank(message: "Un membre doit être associé à la réservation.")]
    private ?Membre $membre = null;

    #[ORM\Column(type: 'string', length: 255, name: 'Nom')]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', length: 255, name: 'Prenom')]
    private ?string $prenom = null;

    #[ORM\Column(type: 'string', length: 255, name: 'Email')]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 255, name: 'Numtel')]
    private ?string $numtel = null;

    #[ORM\Column(type: 'string', length: 255, name: 'Description')]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $date = null;

    public function getIDReservationPack(): ?int
    {
        return $this->IDReservationPack;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getNumtel(): ?string
    {
        return $this->numtel;
    }

    public function setNumtel(string $numtel): self
    {
        $this->numtel = $numtel;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getPack(): ?Pack
    {
        return $this->pack;
    }

    public function setPack(?Pack $pack): self
    {
        $this->pack = $pack;
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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getIDPack(): ?int
    {
        return $this->pack ? $this->pack->getId() : null;
    }
}