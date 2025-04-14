<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\PackServiceRepository;

#[ORM\Entity(repositoryClass: PackServiceRepository::class)]
#[ORM\Table(name: 'pack_service')]
#[ORM\UniqueConstraint(name: 'pack_service_pk', columns: ['pack_id', 'service_titre'])]
class PackService
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    private ?int $pack_id = null;

    #[ORM\Column(type: 'string', nullable: false)]
    #[ORM\Id]
    private ?string $service_titre = null;

    public function getPackId(): ?int
    {
        return $this->pack_id;
    }

    public function setPackId(int $pack_id): self
    {
        $this->pack_id = $pack_id;
        return $this;
    }

    public function getServiceTitre(): ?string
    {
        return $this->service_titre;
    }

    public function setServiceTitre(string $service_titre): self
    {
        $this->service_titre = $service_titre;
        return $this;
    }
}