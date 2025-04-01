<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\MembreRepository;

#[ORM\Entity(repositoryClass: MembreRepository::class)]
#[ORM\Table(name: 'membres')]
class Membre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $Id = null;

    public function getId(): ?int
    {
        return $this->Id;
    }

    public function setId(int $Id): self
    {
        $this->Id = $Id;
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
    private ?string $Prénom = null;

    public function getPrénom(): ?string
    {
        return $this->Prénom;
    }

    public function setPrénom(string $Prénom): self
    {
        $this->Prénom = $Prénom;
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
    private ?string $CIN = null;

    public function getCIN(): ?string
    {
        return $this->CIN;
    }

    public function setCIN(string $CIN): self
    {
        $this->CIN = $CIN;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $NumTel = null;

    public function getNumTel(): ?string
    {
        return $this->NumTel;
    }

    public function setNumTel(string $NumTel): self
    {
        $this->NumTel = $NumTel;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $Adresse = null;

    public function getAdresse(): ?string
    {
        return $this->Adresse;
    }

    public function setAdresse(string $Adresse): self
    {
        $this->Adresse = $Adresse;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $motDePasse = null;

    public function getMotDePasse(): ?string
    {
        return $this->motDePasse;
    }

    public function setMotDePasse(string $motDePasse): self
    {
        $this->motDePasse = $motDePasse;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $Role = null;

    public function getRole(): ?string
    {
        return $this->Role;
    }

    public function setRole(string $Role): self
    {
        $this->Role = $Role;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $image = null;

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $token = null;

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isConfirmed = null;

    public function isIsConfirmed(): ?bool
    {
        return $this->isConfirmed;
    }

    public function setIsConfirmed(?bool $isConfirmed): self
    {
        $this->isConfirmed = $isConfirmed;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Feedback::class, mappedBy: 'membre')]
    private Collection $feedbacks;

    /**
     * @return Collection<int, Feedback>
     */
    public function getFeedbacks(): Collection
    {
        if (!$this->feedbacks instanceof Collection) {
            $this->feedbacks = new ArrayCollection();
        }
        return $this->feedbacks;
    }

    public function addFeedback(Feedback $feedback): self
    {
        if (!$this->getFeedbacks()->contains($feedback)) {
            $this->getFeedbacks()->add($feedback);
        }
        return $this;
    }

    public function removeFeedback(Feedback $feedback): self
    {
        $this->getFeedbacks()->removeElement($feedback);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Reclamation::class, mappedBy: 'membre')]
    private Collection $reclamations;

    public function __construct()
    {
        $this->feedbacks = new ArrayCollection();
        $this->reclamations = new ArrayCollection();
    }

    /**
     * @return Collection<int, Reclamation>
     */
    public function getReclamations(): Collection
    {
        if (!$this->reclamations instanceof Collection) {
            $this->reclamations = new ArrayCollection();
        }
        return $this->reclamations;
    }

    public function addReclamation(Reclamation $reclamation): self
    {
        if (!$this->getReclamations()->contains($reclamation)) {
            $this->getReclamations()->add($reclamation);
        }
        return $this;
    }

    public function removeReclamation(Reclamation $reclamation): self
    {
        $this->getReclamations()->removeElement($reclamation);
        return $this;
    }

    public function isConfirmed(): ?bool
    {
        return $this->isConfirmed;
    }

}
