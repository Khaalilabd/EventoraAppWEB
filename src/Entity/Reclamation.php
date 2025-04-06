<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Reclamation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 50, options: ['default' => 'Autre'])]
    private ?string $Type = null;

    #[ORM\ManyToOne(targetEntity: Membre::class)]
    #[ORM\JoinColumn(name: 'idUser', referencedColumnName: 'id', nullable: false)]
    private ?Membre $membre = null;

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
        if (!in_array($Type, self::TYPES, true)) {
            throw new \InvalidArgumentException("Type invalide : $Type. Les valeurs autorisées sont : " . implode(', ', self::TYPES));
        }
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
}