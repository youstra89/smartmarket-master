<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CustomerRepository")
 */
class Customer
{
    const SEXE = [
        0 => 'Homme',
        1 => 'Femme',
      ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $firstname;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $lastname;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $phone_number;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

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
     * @ORM\Column(type="string", length=255)
     */
    private $address;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $residence;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\CustomerType", inversedBy="customers")
     * @ORM\JoinColumn(nullable=false)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255)
     * @ORM\Column(unique=true)
     */
    private $reference;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\CustomerCommande", mappedBy="customer")
     */
    private $customerCommandes;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $photo;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $profession;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date_naissance;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lieu_naissance;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nature_piece_identite;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $numero_piece_identite;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $numero_compte_bancaire;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $sexe;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $civilite;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date_etablissement_piece_identite;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $date_expiration_piece_identite;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nationalite;


    public function __construct()
    {
        $this->is_deleted        = false;
        $this->created_at        = new \DateTime();
        $this->customerCommandes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSexeType(): string
    {
      return empty($this->sexe) ? 'Indéfini' : self::SEXE[$this->sexe];
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

    public function getLastname(): ?string
    {
        return $this->lastname;
    }
    
    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;
        
        return $this;
    }
    
    public function getName()
    {
        return $this->lastname.' '.$this->firstname;
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

    public function setEmail(?string $email): self
    {
        $this->email = $email;

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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getResidence(): ?string
    {
        return $this->residence;
    }

    public function setResidence(string $residence): self
    {
        $this->residence = $residence;

        return $this;
    }

    public function getType(): ?CustomerType
    {
        return $this->type;
    }

    public function setType(?CustomerType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getMontantTotalCommandeNonSoldees()
    {
        $total = 0;
        foreach ($this->getCustomerCommandes() as $key => $value) {
            if($value->getEnded() === false and $value->getIsDeleted() === false)
                $total = $total + $value->getTotalAmount();
        }
        return $total;
    }

    public function getMontantTotalReglementCommandeNonSoldees()
    {
        $total = 0;
        foreach ($this->getCustomerCommandes() as $key => $value) {
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

    /**
     * @return Collection|CustomerCommande[]
     */
    public function getCustomerCommandes(): Collection
    {
        return $this->customerCommandes;
    }

    public function addCustomerCommande(CustomerCommande $customerCommande): self
    {
        if (!$this->customerCommandes->contains($customerCommande)) {
            $this->customerCommandes[] = $customerCommande;
            $customerCommande->setCustomer($this);
        }

        return $this;
    }

    public function removeCustomerCommande(CustomerCommande $customerCommande): self
    {
        if ($this->customerCommandes->contains($customerCommande)) {
            $this->customerCommandes->removeElement($customerCommande);
            // set the owning side to null (unless already changed)
            if ($customerCommande->getCustomer() === $this) {
                $customerCommande->setCustomer(null);
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

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): self
    {
        $this->photo = $photo;

        return $this;
    }

    public function getProfession(): ?string
    {
        return $this->profession;
    }

    public function setProfession(?string $profession): self
    {
        $this->profession = $profession;

        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->date_naissance;
    }

    public function setDateNaissance(?\DateTimeInterface $date_naissance): self
    {
        $this->date_naissance = $date_naissance;

        return $this;
    }

    public function getLieuNaissance(): ?string
    {
        return $this->lieu_naissance;
    }

    public function setLieuNaissance(?string $lieu_naissance): self
    {
        $this->lieu_naissance = $lieu_naissance;

        return $this;
    }

    public function getNaturePieceIdentite(): ?string
    {
        return $this->nature_piece_identite;
    }

    public function setNaturePieceIdentite(?string $nature_piece_identite): self
    {
        $this->nature_piece_identite = $nature_piece_identite;

        return $this;
    }

    public function getNumeroPieceIdentite(): ?string
    {
        return $this->numero_piece_identite;
    }

    public function setNumeroPieceIdentite(?string $numero_piece_identite): self
    {
        $this->numero_piece_identite = $numero_piece_identite;

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

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(?string $sexe): self
    {
        $this->sexe = $sexe;

        return $this;
    }

    public function getCivilite(): ?string
    {
        return $this->civilite;
    }

    public function setCivilite(?string $civilite): self
    {
        $this->civilite = $civilite;

        return $this;
    }

    public function getDateEtablissementPieceIdentite(): ?\DateTimeInterface
    {
        return $this->date_etablissement_piece_identite;
    }

    public function setDateEtablissementPieceIdentite(?\DateTimeInterface $date_etablissement_piece_identite): self
    {
        $this->date_etablissement_piece_identite = $date_etablissement_piece_identite;

        return $this;
    }

    public function getDateExpirationPieceIdentite(): ?string
    {
        return $this->date_expiration_piece_identite;
    }

    public function setDateExpirationPieceIdentite(?string $date_expiration_piece_identite): self
    {
        $this->date_expiration_piece_identite = $date_expiration_piece_identite;

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
}
