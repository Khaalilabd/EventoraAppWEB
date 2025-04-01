<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\FeedbackRepository;

#[ORM\Entity(repositoryClass: FeedbackRepository::class)]
#[ORM\Table(name: 'feedback')]
class Feedback
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $ID = null;

    public function getID(): ?int
    {
        return $this->ID;
    }

    public function setID(int $ID): self
    {
        $this->ID = $ID;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Membre::class, inversedBy: 'feedbacks')]
    #[ORM\JoinColumn(name: 'idUser', referencedColumnName: 'Id')]
    private ?Membre $membre = null;

    public function getMembre(): ?Membre
    {
        return $this->membre;
    }

    public function setMembre(?Membre $membre): self
    {
        $this->membre = $membre;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $Vote = null;

    public function getVote(): ?int
    {
        return $this->Vote;
    }

    public function setVote(int $Vote): self
    {
        $this->Vote = $Vote;
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

    #[ORM\Column(type: 'blob', nullable: true)]
    private ?string $Souvenirs = null;

    public function getSouvenirs(): ?string
    {
        return $this->Souvenirs;
    }

    public function setSouvenirs(?string $Souvenirs): self
    {
        $this->Souvenirs = $Souvenirs;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $Recommend = null;

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
