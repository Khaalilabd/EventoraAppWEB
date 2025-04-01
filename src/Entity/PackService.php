<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\PackServiceRepository;

#[ORM\Entity(repositoryClass: PackServiceRepository::class)]
#[ORM\Table(name: 'pack_service')]
class PackService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $pack_id = null;

    public function getPack_id(): ?int
    {
        return $this->pack_id;
    }

    public function setPack_id(int $pack_id): self
    {
        $this->pack_id = $pack_id;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $service_titre = null;

    public function getService_titre(): ?string
    {
        return $this->service_titre;
    }

    public function setService_titre(string $service_titre): self
    {
        $this->service_titre = $service_titre;
        return $this;
    }

    public function getPackId(): ?int
    {
        return $this->pack_id;
    }

    public function getServiceTitre(): ?string
    {
        return $this->service_titre;
    }

    public function setServiceTitre(string $service_titre): static
    {
        $this->service_titre = $service_titre;

        return $this;
    }

}
