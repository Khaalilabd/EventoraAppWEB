<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\FeedbackRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FeedbackRepository::class)]
#[ORM\Table(name: 'feedback')]
class Feedback
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $ID = null;

    #[ORM\ManyToOne(targetEntity: Membre::class, inversedBy: 'feedbacks')]
    #[ORM\JoinColumn(name: 'idUser', referencedColumnName: 'id', nullable: false)]
    private ?Membre $membre = null;

    #[ORM\Column(type: 'integer', nullable: false)]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'Le vote doit être entre 1 et 5.')]
    private ?int $Vote = null;

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'La description ne peut pas être vide.')]
    private ?string $Description = null;

    #[ORM\Column(type: 'blob', nullable: true)]
    private $Souvenirs = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $Recommend = null;

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $date = null;

    /**
     * @var UploadedFile|null
     */
    private $souvenirsFile;

    // Getters et setters
    public function getID(): ?int
    {
        return $this->ID;
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

    public function getSouvenirs()
    {
        return $this->Souvenirs;
    }

    public function setSouvenirs($Souvenirs): self
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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getSouvenirsFile(): ?UploadedFile
    {
        return $this->souvenirsFile;
    }

    public function setSouvenirsFile(?UploadedFile $souvenirsFile): self
    {
        $this->souvenirsFile = $souvenirsFile;
        return $this;
    }
}