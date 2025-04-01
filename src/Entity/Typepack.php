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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $type = null;

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Pack::class, mappedBy: 'typepack')]
    private Collection $packs;

    public function __construct()
    {
        $this->packs = new ArrayCollection();
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
        }
        return $this;
    }

    public function removePack(Pack $pack): self
    {
        $this->getPacks()->removeElement($pack);
        return $this;
    }

}
