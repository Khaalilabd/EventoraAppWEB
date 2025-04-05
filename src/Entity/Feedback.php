<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\FeedbackRepository;

#[ORM\Entity(repositoryClass: FeedbackRepository::class)]
#[ORM\Table(name: 'feedback')]
class Feedback
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $ID = null;

    #[ORM\ManyToOne(targetEntity: Membre::class, inversedBy: 'feedbacks')]
    #[ORM\JoinColumn(name: 'idUser', referencedColumnName: 'id')] // Changé 'Id' en 'id'
    private ?Membre $membre = null;

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $Vote = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $Description = null;

    #[ORM\Column(type: 'blob', nullable: true)]
    private ?string $Souvenirs = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $Recommend = null;

    // Getters et setters
    public function getID(): ?int
    {
        return $this->ID;
    }

    public function setID(int $ID): self
    {
        $this->ID = $ID;
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

    public function getVote(): ?int
    {
        return $this->Vote;
    }

    public function setVote(int $Vote): self
    {
        $this->Vote = $Vote;
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

    public function getSouvenirs(): ?string
    {
        return $this->Souvenirs;
    }

    public function setSouvenirs(?string $Souvenirs): self
    {
        $this->Souvenirs = $Souvenirs;
        return $this;
    }

    public function getRecommend(): ?string
    {
        return $this->Recommend;
    }

    public function setRecommend(?string $Recommend): self
    {
        $this->Recommend = $Recommend;
        return $this;
    }
}