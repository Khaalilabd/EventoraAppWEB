<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Reclamation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Le titre ne peut pas être vide.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $titre = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: "La description ne peut pas être vide.")]
    #[Assert\Length(
        min: 10,
        max: 1000,
        minMessage: "La description doit contenir au moins {{ limit }} caractères.",
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 50, options: ['default' => 'Autre'])]
    #[Assert\NotBlank(message: "Le type ne peut pas être vide.")]
    #[Assert\Choice(
        choices: self::TYPES,
        message: "Le type '{{ value }}' n'est pas valide. Choisissez parmi : {{ choices }}."
    )]
    private ?string $Type = null;

    #[ORM\ManyToOne(targetEntity: Membre::class)]
    #[ORM\JoinColumn(name: 'idUser', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull(message: "Le membre doit être spécifié.")]
    private ?Membre $membre = null;

    #[ORM\Column(type: 'string', length: 50, nullable: false, options: ['default' => 'En_Attente'])]
    #[Assert\NotBlank(message: "Le statut ne peut pas être vide.")]
    #[Assert\Choice(
        choices: self::STATUTS,
        message: "Le statut '{{ value }}' n'est pas valide. Choisissez parmi : {{ choices }}."
    )]
    private ?string $statut = null;

    // Constantes pour les valeurs possibles de Type
    public const TYPE_PACKS = 'Packs';
    public const TYPE_SERVICE = 'Service';
    public const TYPE_PROBLEME_TECHNIQUE = 'Problème Technique';
    public const TYPE_PLAINTE_AGENT = 'Plainte entre un Agent de contrôle';
    public const TYPE_AUTRE = 'Autre';

    // Liste des types valides
    public const TYPES = [
        self::TYPE_PACKS,
        self::TYPE_SERVICE,
        self::TYPE_PROBLEME_TECHNIQUE,
        self::TYPE_PLAINTE_AGENT,
        self::TYPE_AUTRE,
    ];

    // Constantes pour les valeurs possibles de Statut
    public const STATUT_EN_ATTENTE = 'En_Attente';
    public const STATUT_EN_COURS = 'En_Cours';
    public const STATUT_RESOLU = 'Resolue';
    public const STATUT_REJETE = 'Rejetée';

    // Liste des statuts valides
    public const STATUTS = [
        self::STATUT_EN_ATTENTE,
        self::STATUT_EN_COURS,
        self::STATUT_RESOLU,
        self::STATUT_REJETE,
    ];

    // Getters et setters
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
        $this->Type = $Type; // La validation est gérée par Assert\Choice
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
        $this->statut = $statut; // La validation est gérée par Assert\Choice
        return $this;
    }
}