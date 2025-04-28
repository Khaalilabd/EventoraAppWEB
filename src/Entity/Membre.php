<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\MembreRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MembreRepository::class)]
#[ORM\Table(name: 'membres')]
class Membre implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', nullable: false, name: 'Nom')]
    #[Assert\NotBlank(message: "Le nom ne doit pas être vide.")]
    #[Assert\Length(min: 2, minMessage: "Le nom doit contenir au moins {{ limit }} caractères.")]
    private string $nom = '';

    #[ORM\Column(type: 'string', nullable: false, name: 'Prénom')]
    #[Assert\NotBlank(message: "Le prénom ne doit pas être vide.")]
    #[Assert\Length(min: 2, minMessage: "Le prénom doit contenir au moins {{ limit }} caractères.")]
    private string $prenom = '';

    #[ORM\Column(type: 'string', nullable: false, name: 'Email', unique: true)]
    #[Assert\NotBlank(message: "L'email ne doit pas être vide.")]
    #[Assert\Email(message: "L'adresse email '{{ value }}' n'est pas un email valide.")]
    private string $email = '';

    #[ORM\Column(type: 'string', nullable: false, name: 'CIN')]
    #[Assert\NotBlank(message: "Le numéro de CIN ne doit pas être vide.")]
    #[Assert\Regex(
        pattern: '/^[0-9]{8}$/',
        message: "Le numéro de CIN doit être composé de 8 chiffres exactement."
    )]
    private string $cin = '';

    #[ORM\Column(type: 'string', nullable: false, name: 'NumTel')]
    #[Assert\NotBlank(message: "Le numéro de téléphone ne doit pas être vide.")]
    #[Assert\Regex(
        pattern: '/^[24579][0-9]{7}$/',
        message: "Veuillez entrer un numéro de téléphone tunisien valide (8 chiffres, commençant par 2, 4, 5, 7 ou 9)."
    )]
    private string $numTel = '';

    #[ORM\Column(type: 'string', nullable: false, name: 'Adresse')]
    #[Assert\NotBlank(message: "L'adresse ne doit pas être vide.")]
    #[Assert\Length(min: 5, minMessage: "L'adresse complète doit contenir au moins {{ limit }} caractères.")]
    private string $adresse = '';

    #[ORM\Column(type: 'string', nullable: false, name: 'motDePasse')]
    private string $motDePasse = '';

    #[ORM\Column(type: 'string', nullable: false, name: 'Role')]
    private string $role = '';

    #[ORM\Column(type: 'string', nullable: true, name: 'image')]
    private ?string $image = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $token = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $tokenExpiration = null;

    #[ORM\Column(type: 'boolean', nullable: true, name: 'isConfirmed')]
    private ?bool $isConfirmed = false;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Assert\Type(\DateTimeInterface::class, message: "La date de naissance doit être au format date valide.")]
    #[Assert\Past(message: "La date de naissance doit être dans le passé.")]
    private ?\DateTimeInterface $dateOfBirth = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Assert\Choice(choices: ['Homme', 'Femme'], message: "Veuillez sélectionner votre genre : Homme ou Femme.")]
    private ?string $gender = null;

    #[ORM\Column(type: 'string', length: 180, nullable: true, unique: true)]
    private ?string $username = null;

    #[ORM\OneToMany(targetEntity: Feedback::class, mappedBy: 'membre')]
    private Collection $feedbacks;

    #[ORM\OneToMany(targetEntity: Reclamation::class, mappedBy: 'membre')]
    private Collection $reclamations;

    #[ORM\OneToMany(targetEntity: Reservationpack::class, mappedBy: 'membre')]
    private Collection $reservationpacks;

    #[ORM\OneToMany(targetEntity: Reservationpersonnalise::class, mappedBy: 'membre')]
    private Collection $reservationpersonnalises;

    public function __construct()
    {
        $this->feedbacks = new ArrayCollection();
        $this->reclamations = new ArrayCollection();
        $this->reservationpacks = new ArrayCollection();
        $this->reservationpersonnalises = new ArrayCollection();
    }

    public function getRoles(): array
    {
        $role = $this->role;
        if (empty($role)) {
            return ['ROLE_USER'];
        }
        if (!str_starts_with($role, 'ROLE_')) {
            $role = 'ROLE_' . strtoupper($role);
        }
        return [$role];
    }

    public function getPassword(): ?string
    {
        return $this->motDePasse;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getCin(): string
    {
        return $this->cin;
    }

    public function setCin(string $cin): self
    {
        $this->cin = $cin;
        return $this;
    }

    public function getNumTel(): string
    {
        return $this->numTel;
    }

    public function setNumTel(string $numTel): self
    {
        $this->numTel = $numTel;
        return $this;
    }

    public function getAdresse(): string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): self
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getMotDePasse(): string
    {
        return $this->motDePasse;
    }

    public function setMotDePasse(string $motDePasse): self
    {
        $this->motDePasse = $motDePasse;
        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;
        return $this;
    }

    public function getTokenExpiration(): ?\DateTimeInterface
    {
        return $this->tokenExpiration;
    }

    public function setTokenExpiration(?\DateTimeInterface $tokenExpiration): self
    {
        $this->tokenExpiration = $tokenExpiration;
        return $this;
    }

    public function isConfirmed(): ?bool
    {
        return $this->isConfirmed;
    }

    public function setIsConfirmed(?bool $isConfirmed): self
    {
        $this->isConfirmed = $isConfirmed;
        return $this;
    }

    public function getDateOfBirth(): ?\DateTimeInterface
    {
        return $this->dateOfBirth;
    }

    public function setDateOfBirth(?\DateTimeInterface $dateOfBirth): self
    {
        $this->dateOfBirth = $dateOfBirth;
        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return Collection<int, Feedback>
     */
    public function getFeedbacks(): Collection
    {
        return $this->feedbacks;
    }

    public function addFeedback(Feedback $feedback): self
    {
        if (!$this->feedbacks->contains($feedback)) {
            $this->feedbacks[] = $feedback;
        }
        return $this;
    }

    public function removeFeedback(Feedback $feedback): self
    {
        $this->feedbacks->removeElement($feedback);
        return $this;
    }

    /**
     * @return Collection<int, Reclamation>
     */
    public function getReclamations(): Collection
    {
        return $this->reclamations;
    }

    public function addReclamation(Reclamation $reclamation): self
    {
        if (!$this->reclamations->contains($reclamation)) {
            $this->reclamations[] = $reclamation;
        }
        return $this;
    }

    public function removeReclamation(Reclamation $reclamation): self
    {
        $this->reclamations->removeElement($reclamation);
        return $this;
    }

    /**
     * @return Collection<int, Reservationpack>
     */
    public function getReservationpacks(): Collection
    {
        return $this->reservationpacks;
    }

    public function addReservationpack(Reservationpack $reservationpack): self
    {
        if (!$this->reservationpacks->contains($reservationpack)) {
            $this->reservationpacks[] = $reservationpack;
            $reservationpack->setMembre($this);
        }
        return $this;
    }

    public function removeReservationpack(Reservationpack $reservationpack): self
    {
        if ($this->reservationpacks->removeElement($reservationpack)) {
            if ($reservationpack->getMembre() === $this) {
                $reservationpack->setMembre(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Reservationpersonnalise>
     */
    public function getReservationpersonnalises(): Collection
    {
        return $this->reservationpersonnalises;
    }

    public function addReservationpersonnalise(Reservationpersonnalise $reservationpersonnalise): self
    {
        if (!$this->reservationpersonnalises->contains($reservationpersonnalise)) {
            $this->reservationpersonnalises[] = $reservationpersonnalise;
            $reservationpersonnalise->setMembre($this);
        }
        return $this;
    }

    public function removeReservationpersonnalise(Reservationpersonnalise $reservationpersonnalise): self
    {
        if ($this->reservationpersonnalises->removeElement($reservationpersonnalise)) {
            if ($reservationpersonnalise->getMembre() === $this) {
                $reservationpersonnalise->setMembre(null);
            }
        }
        return $this;
    }
}