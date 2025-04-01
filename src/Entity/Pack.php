<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\PackRepository;

#[ORM\Entity(repositoryClass: PackRepository::class)]
#[ORM\Table(name: 'pack')]
class Pack
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
    private ?string $nomPack = null;

    public function getNomPack(): ?string
    {
        return $this->nomPack;
    }

    public function setNomPack(string $nomPack): self
    {
        $this->nomPack = $nomPack;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $prix = null;

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): self
    {
        $this->prix = $prix;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $location = null;

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Typepack::class, inversedBy: 'packs')]
    #[ORM\JoinColumn(name: 'type', referencedColumnName: 'type')]
    private ?Typepack $typepack = null;

    public function getTypepack(): ?Typepack
    {
        return $this->typepack;
    }

    public function setTypepack(?Typepack $typepack): self
    {
        $this->typepack = $typepack;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $nbrGuests = null;

    public function getNbrGuests(): ?int
    {
        return $this->nbrGuests;
    }

    public function setNbrGuests(int $nbrGuests): self
    {
        $this->nbrGuests = $nbrGuests;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $image_path = null;

    public function getImage_path(): ?string
    {
        return $this->image_path;
    }

    public function setImage_path(string $image_path): self
    {
        $this->image_path = $image_path;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Reservationpack::class, mappedBy: 'pack')]
    private Collection $reservationpacks;

    public function __construct()
    {
        $this->reservationpacks = new ArrayCollection();
    }

    /**
     * @return Collection<int, Reservationpack>
     */
    public function getReservationpacks(): Collection
    {
        if (!$this->reservationpacks instanceof Collection) {
            $this->reservationpacks = new ArrayCollection();
        }
        return $this->reservationpacks;
    }

    public function addReservationpack(Reservationpack $reservationpack): self
    {
        if (!$this->getReservationpacks()->contains($reservationpack)) {
            $this->getReservationpacks()->add($reservationpack);
        }
        return $this;
    }

    public function removeReservationpack(Reservationpack $reservationpack): self
    {
        $this->getReservationpacks()->removeElement($reservationpack);
        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->image_path;
    }

    public function setImagePath(string $image_path): static
    {
        $this->image_path = $image_path;

        return $this;
    }

}
