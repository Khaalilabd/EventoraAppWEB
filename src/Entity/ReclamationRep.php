<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: "reclamationrep")]
class ReclamationRep
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Reclamation::class, inversedBy: 'reclamationReps')]
    #[ORM\JoinColumn(name: 'idRec', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull(message: "La réclamation doit être spécifiée.")]
    private ?Reclamation $reclamation = null;

    #[ORM\Column(type: 'string', length: 5000)]
    #[Assert\NotBlank(message: "La réponse ne peut pas être vide.")]
    #[Assert\Length(
        min: 10,
        max: 50000,
        minMessage: "La réponse doit contenir au moins {{ limit }} caractères.",
        maxMessage: "La réponse ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $reponse = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date = null;

    public function __construct()
    {
        $this->date = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReclamation(): ?Reclamation
    {
        return $this->reclamation;
    }

    public function setReclamation(?Reclamation $reclamation): self
    {
        $this->reclamation = $reclamation;
        return $this;
    }

    public function getReponse(): ?string
    {
        return $this->reponse;
    }

    public function setReponse(string $reponse): self
    {
        $this->reponse = $reponse;
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
}