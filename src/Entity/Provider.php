<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProviderRepository")
 */
class Provider
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @ORM\Column(unique=true)
     */
    private $reference;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $firstname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lastname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $phone_number;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $city;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $created_by;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated_at;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $updated_by;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_deleted;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deleted_at;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $deleted_by;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Country", inversedBy="providers")
     * @ORM\JoinColumn(nullable=false)
     */
    private $country;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ProviderCommande", mappedBy="provider")
     */
    private $providerCommandes;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $numero_compte_bancaire;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nationalite;

    /**
     * @ORM\Column(type="integer")
     */
    private $acompte;


    public function __construct()
    {
        $this->acompte        = 0;
        $this->is_deleted     = false;
        $this->created_at     = new \DateTime();
        $this->providerCommandes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function getMontantTotalCommandeNonSoldees()
    {
        $total = 0;
        foreach ($this->getProviderCommandes() as $key => $value) {
            if($value->getEnded() === false and $value->getIsDeleted() === false)
                $total = $total + $value->getNetAPayer();
        }
        return $total;
    }

    public function getMontantTotalReglementCommandeNonSoldees()
    {
        $total = 0;
        foreach ($this->getProviderCommandes() as $key => $value) {
            if($value->getEnded() === false and $value->getIsDeleted() === false)
            {
                foreach ($value->getSettlements() as $settlement) {
                    if ($settlement->getIsDeleted() === false) {
                        $total = $total + $settlement->getAmount();
                    }
                }
            }
        }
        return $total;
    }

    public function setReference(string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getNom()
    {
        if(empty($this->lastname))
            return $this->firstname;
        return $this->firstname.' '.$this->lastname;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phone_number;
    }

    public function setPhoneNumber(string $phone_number): self
    {
        $this->phone_number = $phone_number;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): self
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): self
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return Collection|ProviderCommande[]
     */
    public function getProviderCommandes(): Collection
    {
        return $this->providerCommandes;
    }

    public function addProviderCommande(ProviderCommande $providerCommande): self
    {
        if (!$this->providerCommandes->contains($providerCommande)) {
            $this->providerOrders[] = $providerCommande;
            $providerCommande->setProvider($this);
        }

        return $this;
    }

    public function removeProviderCommande(ProviderCommande $providerCommande): self
    {
        if ($this->providerCommandes->contains($providerCommande)) {
            $this->providerCommandes->removeElement($providerCommande);
            // set the owning side to null (unless already changed)
            if ($providerCommande->getProvider() === $this) {
                $providerCommande->setProvider(null);
            }
        }

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->created_by;
    }

    public function setCreatedBy(?User $created_by): self
    {
        $this->created_by = $created_by;

        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updated_by;
    }

    public function setUpdatedBy(?User $updated_by): self
    {
        $this->updated_by = $updated_by;

        return $this;
    }

    public function getIsDeleted(): ?bool
    {
        return $this->is_deleted;
    }

    public function setIsDeleted(bool $is_deleted): self
    {
        $this->is_deleted = $is_deleted;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deleted_at;
    }

    public function setDeletedAt(?\DateTimeInterface $deleted_at): self
    {
        $this->deleted_at = $deleted_at;

        return $this;
    }

    public function getDeletedBy(): ?User
    {
        return $this->deleted_by;
    }

    public function setDeletedBy(?User $deleted_by): self
    {
        $this->deleted_by = $deleted_by;

        return $this;
    }

    public function getNumeroCompteBancaire(): ?string
    {
        return $this->numero_compte_bancaire;
    }

    public function setNumeroCompteBancaire(?string $numero_compte_bancaire): self
    {
        $this->numero_compte_bancaire = $numero_compte_bancaire;

        return $this;
    }

    public function getNationalite(): ?string
    {
        return $this->nationalite;
    }

    public function setNationalite(?string $nationalite): self
    {
        $this->nationalite = $nationalite;

        return $this;
    }

    public function getAcompte(): ?int
    {
        return $this->acompte;
    }

    public function setAcompte(int $acompte): self
    {
        $this->acompte = $acompte;

        return $this;
    }
}
