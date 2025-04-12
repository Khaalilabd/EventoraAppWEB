<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\TypepackRepository;

#[ORM\Entity(repositoryClass: TypepackRepository::class)]
#[ORM\Table(name: 'typepack')]
class Typepack
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $type = null;

    #[ORM\OneToMany(targetEntity: Pack::class, mappedBy: 'typepack')]
    private Collection $packs;

    public function __construct()
    {
        $this->packs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
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
            $pack->setTypepack($this); // Ensure bidirectional relationship
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