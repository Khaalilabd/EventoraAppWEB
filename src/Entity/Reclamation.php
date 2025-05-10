<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: "reclamation")]
class Reclamation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: "The title cannot be empty.")]
    #[Assert\Length(
        max: 100,
        maxMessage: "The title cannot exceed {{ limit }} characters."
    )]
    private ?string $titre = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: "The description cannot be empty.")]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 50, options: ['default' => 'Autre'])]
    #[Assert\NotBlank(message: "The type cannot be empty.")]
    #[Assert\Choice(
        choices: self::TYPES,
        message: "The type '{{ value }}' is not valid. Choose from: {{ choices }}."
    )]
    private ?string $Type = null;

    #[ORM\ManyToOne(targetEntity: Membre::class)]
    #[ORM\JoinColumn(name: 'idUser', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull(message: "The member must be specified.")]
    private ?Membre $membre = null;

    #[ORM\Column(type: 'string', length: 50, nullable: false, options: ['default' => 'En_Attente'])]
    #[Assert\NotBlank(message: "The status cannot be empty.")]
    #[Assert\Choice(
        choices: self::STATUTS,
        message: "The status '{{ value }}' is not valid. Choose from: {{ choices }}."
    )]
    private ?string $statut = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $qrCodeUrl = null;

    #[ORM\OneToMany(mappedBy: 'reclamation', targetEntity: ReclamationRep::class, fetch: 'EXTRA_LAZY')]
    private Collection $reclamationReps;

    // Constants for possible Type values
    public const TYPE_PACKS = 'Packs';
    public const TYPE_SERVICE = 'Service';
    public const TYPE_PROBLEME_TECHNIQUE = 'Problème Technique';
    public const TYPE_PLAINTE_AGENT = 'Plainte entre un Agent de contrôle';
    public const TYPE_AUTRE = 'Autre';

    public const TYPES = [
        self::TYPE_PACKS,
        self::TYPE_SERVICE,
        self::TYPE_PROBLEME_TECHNIQUE,
        self::TYPE_PLAINTE_AGENT,
        self::TYPE_AUTRE,
    ];

    // Constants for possible Status values
    public const STATUT_EN_ATTENTE = 'En_Attente';
    public const STATUT_EN_COURS = 'En_Cours';
    public const STATUT_RESOLU = 'Resolue';
    public const STATUT_REJETE = 'Rejetée';

    public const STATUTS = [
        self::STATUT_EN_ATTENTE,
        self::STATUT_EN_COURS,
        self::STATUT_RESOLU,
        self::STATUT_REJETE,
    ];

    public function __construct()
    {
        $this->reclamationReps = new ArrayCollection();
        $this->date = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->Type;
    }

    public function setType(string $Type): self
    {
        $this->Type = $Type;
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

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
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

    public function getQrCodeUrl(): ?string
    {
        return $this->qrCodeUrl;
    }

    public function setQrCodeUrl(?string $qrCodeUrl): self
    {
        $this->qrCodeUrl = $qrCodeUrl;
        return $this;
    }

    /**
     * @return Collection|ReclamationRep[]
     */
    public function getReclamationReps(): Collection
    {
        return $this->reclamationReps;
    }

    public function addReclamationRep(ReclamationRep $reclamationRep): self
    {
        if (!$this->reclamationReps->contains($reclamationRep)) {
            $this->reclamationReps[] = $reclamationRep;
            $reclamationRep->setReclamation($this);
        }

        return $this;
    }

    public function removeReclamationRep(ReclamationRep $reclamationRep): self
    {
        if ($this->reclamationReps->removeElement($reclamationRep)) {
            if ($reclamationRep->getReclamation() === $this) {
                $reclamationRep->setReclamation(null);
            }
        }

        return $this;
    }
}