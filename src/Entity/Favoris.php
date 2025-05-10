<?php

namespace App\Entity;

use App\Repository\FavorisRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FavorisRepository::class)]
#[ORM\Table(name: 'favoris')]
class Favoris
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Pack::class, inversedBy: 'favoris')]
    #[ORM\JoinColumn(name: 'pack_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Pack $pack = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Membre::class, inversedBy: 'favoris')]
    #[ORM\JoinColumn(name: 'membre_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Membre $membre = null;

    public function getPack(): ?Pack
    {
        return $this->pack;
    }

    public function setPack(?Pack $pack): self
    {
        $this->pack = $pack;
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