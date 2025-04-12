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

    #[ORM\Column(type: 'string', length: 255, name: 'nomPack')]
    private ?string $nomPack = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: false)]
    private ?string $prix = null;

    #[ORM\Column(type: 'string', name: 'location')]
    private ?string $location = null;

    #[ORM\ManyToOne(targetEntity: Typepack::class, inversedBy: 'packs')]
    #[ORM\JoinColumn(name: 'type', referencedColumnName: 'id', nullable: false)]
    private ?Typepack $typepack = null;

    #[ORM\Column(type: 'integer', name: 'nbrGuests')]
    private ?int $nbrGuests = null;

    #[ORM\Column(type: 'string', length: 255, name: 'image_path')]
    private ?string $image_path = null;

    #[ORM\OneToMany(targetEntity: Reservationpack::class, mappedBy: 'pack')]
    private Collection $reservationpacks;

    public function __construct()
    {
        $this->reservationpacks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomPack(): ?string
    {
        return $this->nomPack;
    }

    public function setNomPack(string $nomPack): self
    {
        $this->nomPack = $nomPack;
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

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): self
    {
        $this->prix = $prix;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getTypepack(): ?Typepack
    {
        return $this->typepack;
    }

    public function setTypepack(?Typepack $typepack): self
    {
        if ($typepack && $typepack->getId() === null) {
            throw new \InvalidArgumentException('Typepack must have a valid ID.');
        }
        $this->typepack = $typepack;
        return $this;
    }

    public function getNbrGuests(): ?int
    {
        return $this->nbrGuests;
    }

    public function setNbrGuests(int $nbrGuests): self
    {
        $this->nbrGuests = $nbrGuests;
        return $this;
    }

    public function getImage_path(): ?string
    {
        return $this->image_path;
    }

    public function setImage_path(string $image_path): self
    {
        $this->image_path = $image_path;
        return $this;
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
            $reservationpack->setPack($this);
        }
        return $this;
    }

    public function removeReservationpack(Reservationpack $reservationpack): self
    {
        if ($this->getReservationpacks()->removeElement($reservationpack)) {
            if ($reservationpack->getPack() === $this) {
                $reservationpack->setPack(null);
            }
        }
        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->image_path;
    }

    public function setImagePath(string $image_path): self
    {
        $this->image_path = $image_path;
        return $this;
    }
}