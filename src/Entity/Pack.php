<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\PackRepository;
use App\Entity\Typepack;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PackRepository::class)]
#[ORM\Table(name: 'pack')]
#[UniqueEntity(fields: ['nomPack'], message: 'Ce nom de pack existe déjà.')]
class Pack
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', nullable: false, name: 'nomPack')]
    #[Assert\NotBlank(message: 'Le nom du pack ne peut pas être vide.')]
    private ?string $nomPack = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', nullable: false)]
    #[Assert\NotBlank(message: 'Le prix ne peut pas être vide.')]
    #[Assert\GreaterThanOrEqual(0, message: 'Le prix doit être supérieur ou égal à 0.')]
    private ?float $prix = null;

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Le lieu ne peut pas être vide.')]
    #[Assert\Choice(
        choices: ['HOTEL', 'MAISON_D_HOTE', 'ESPACE_VERT', 'SALLE_DE_FETE', 'AUTRE'],
        message: 'Veuillez sélectionner un lieu valide.'
    )]
    private ?string $location = null;

    #[ORM\Column(type: 'string', nullable: false, name: 'type')]
    #[Assert\NotBlank(message: 'Le type ne peut pas être vide.')]
    private ?string $type = null;

    #[ORM\Column(type: 'integer', nullable: false, name: 'nbrGuests')]
    #[Assert\NotBlank(message: 'Le nombre d\'invités ne peut pas être vide.')]
    #[Assert\GreaterThanOrEqual(1, message: 'Le nombre d\'invités doit être au moins 1.')]
    private ?int $nbrGuests = null;

    #[ORM\Column(type: 'string', nullable: false, name: 'image_path')]
    private ?string $imagePath = null;

    #[ORM\OneToMany(targetEntity: Reservationpack::class, mappedBy: 'pack')]
    private Collection $reservationpacks;

    private ?Typepack $typepack = null;

    private array $services = [];

    public function __construct()
    {
        $this->reservationpacks = new ArrayCollection();
        $this->services = [];
        $this->imagePath = '/uploads/images/default.jpg';
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

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): self
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
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

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): self
    {
        $this->imagePath = $imagePath ?? '/uploads/images/default.jpg';
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

    public function getTypepack(): ?Typepack
    {
        return $this->typepack;
    }

    public function setTypepack(?Typepack $typepack): self
    {
        $this->typepack = $typepack;
        if ($typepack !== null) {
            $this->type = $typepack->getType();
        }
        return $this;
    }

    public function getServices(): array
    {
        return $this->services;
    }

    public function setServices(array $services): self
    {
        $this->services = $services;
        return $this;
    }
}