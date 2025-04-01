<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\SponsorRepository;

#[ORM\Entity(repositoryClass: SponsorRepository::class)]
#[ORM\Table(name: 'sponsors')]
class Sponsor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_partenaire = null;

    public function getId_partenaire(): ?int
    {
        return $this->id_partenaire;
    }

    public function setId_partenaire(int $id_partenaire): self
    {
        $this->id_partenaire = $id_partenaire;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $nom_partenaire = null;

    public function getNom_partenaire(): ?string
    {
        return $this->nom_partenaire;
    }

    public function setNom_partenaire(string $nom_partenaire): self
    {
        $this->nom_partenaire = $nom_partenaire;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $email_partenaire = null;

    public function getEmail_partenaire(): ?string
    {
        return $this->email_partenaire;
    }

    public function setEmail_partenaire(string $email_partenaire): self
    {
        $this->email_partenaire = $email_partenaire;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $telephone_partenaire = null;

    public function getTelephone_partenaire(): ?string
    {
        return $this->telephone_partenaire;
    }

    public function setTelephone_partenaire(string $telephone_partenaire): self
    {
        $this->telephone_partenaire = $telephone_partenaire;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $adresse_partenaire = null;

    public function getAdresse_partenaire(): ?string
    {
        return $this->adresse_partenaire;
    }

    public function setAdresse_partenaire(string $adresse_partenaire): self
    {
        $this->adresse_partenaire = $adresse_partenaire;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $site_web = null;

    public function getSite_web(): ?string
    {
        return $this->site_web;
    }

    public function setSite_web(string $site_web): self
    {
        $this->site_web = $site_web;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $type_partenaire = null;

    public function getType_partenaire(): ?string
    {
        return $this->type_partenaire;
    }

    public function setType_partenaire(string $type_partenaire): self
    {
        $this->type_partenaire = $type_partenaire;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: GService::class, mappedBy: 'sponsor')]
    private Collection $gServices;

    public function __construct()
    {
        $this->gServices = new ArrayCollection();
    }

    /**
     * @return Collection<int, GService>
     */
    public function getGServices(): Collection
    {
        if (!$this->gServices instanceof Collection) {
            $this->gServices = new ArrayCollection();
        }
        return $this->gServices;
    }

    public function addGService(GService $gService): self
    {
        if (!$this->getGServices()->contains($gService)) {
            $this->getGServices()->add($gService);
        }
        return $this;
    }

    public function removeGService(GService $gService): self
    {
        $this->getGServices()->removeElement($gService);
        return $this;
    }

    public function getIdPartenaire(): ?int
    {
        return $this->id_partenaire;
    }

    public function getNomPartenaire(): ?string
    {
        return $this->nom_partenaire;
    }

    public function setNomPartenaire(string $nom_partenaire): static
    {
        $this->nom_partenaire = $nom_partenaire;

        return $this;
    }

    public function getEmailPartenaire(): ?string
    {
        return $this->email_partenaire;
    }

    public function setEmailPartenaire(string $email_partenaire): static
    {
        $this->email_partenaire = $email_partenaire;

        return $this;
    }

    public function getTelephonePartenaire(): ?string
    {
        return $this->telephone_partenaire;
    }

    public function setTelephonePartenaire(string $telephone_partenaire): static
    {
        $this->telephone_partenaire = $telephone_partenaire;

        return $this;
    }

    public function getAdressePartenaire(): ?string
    {
        return $this->adresse_partenaire;
    }

    public function setAdressePartenaire(string $adresse_partenaire): static
    {
        $this->adresse_partenaire = $adresse_partenaire;

        return $this;
    }

    public function getSiteWeb(): ?string
    {
        return $this->site_web;
    }

    public function setSiteWeb(string $site_web): static
    {
        $this->site_web = $site_web;

        return $this;
    }

    public function getTypePartenaire(): ?string
    {
        return $this->type_partenaire;
    }

    public function setTypePartenaire(string $type_partenaire): static
    {
        $this->type_partenaire = $type_partenaire;

        return $this;
    }

}
