<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\TypepackRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: TypepackRepository::class)]
#[ORM\Table(name: 'typepack')]
#[UniqueEntity(fields: ['type'], message: 'Ce type existe déjà.')]
class Typepack
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Le type ne peut pas être vide.')]
    private string $type;

    #[ORM\OneToMany(targetEntity: Pack::class, mappedBy: 'typepack')]
    private Collection $packs;

    public function __construct()
    {
        $this->packs = new ArrayCollection();
        $this->type = ''; // Initialize to empty string to avoid null
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return Collection<int, Pack>
     */
    public function getPacks(): Collection
    {
        if (!$this->packs instanceof Collection) {
            $this->packs = new ArrayCollection();
        }
        return $this->packs;
    }

    public function addPack(Pack $pack): self
    {
        if (!$this->getPacks()->contains($pack)) {
            $this->getPacks()->add($pack);
            $pack->setTypepack($this);
        }
        return $this;
    }

    public function removePack(Pack $pack): self
    {
        if ($this->getPacks()->removeElement($pack)) {
            if ($pack->getTypepack() === $this) {
                $pack->setTypepack(null);
            }
        }
        return $this;
    }
}