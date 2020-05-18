<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ComptaExerciceRepository")
 */
class ComptaExercice
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date_debut;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date_fin;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $label;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $created_by;

    /**
     * @ORM\Column(type="datetime", nullable=true)
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
     * @ORM\OneToMany(targetEntity="App\Entity\ComptaEcriture", mappedBy="exercice")
     */
    private $comptaEcritures;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ComptaCompteExercice", mappedBy="exercice")
     */
    private $comptaCompteExercices;

    /**
     * @ORM\Column(type="boolean")
     */
    private $acheve;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\CustomerCommande", mappedBy="exercice")
     */
    private $customerCommandes;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ProviderCommande", mappedBy="exercice")
     */
    private $providerCommandes;

    public function __construct()
    {
        $this->acheve         = false;
        $this->is_deleted     = false;
        $this->created_at     = new \DateTime();
        $this->comptaEcritures = new ArrayCollection();
        $this->comptaCompteExercices = new ArrayCollection();
        $this->customerCommandes = new ArrayCollection();
        $this->providerCommandes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->date_debut;
    }

    public function setDateDebut(\DateTimeInterface $date_debut): self
    {
        $this->date_debut = $date_debut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->date_fin;
    }

    public function setDateFin(\DateTimeInterface $date_fin): self
    {
        $this->date_fin = $date_fin;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return Collection|ComptaEcriture[]
     */
    public function getComptaEcritures(): Collection
    {
        return $this->comptaEcritures;
    }

    public function addComptaEcriture(ComptaEcriture $comptaEcriture): self
    {
        if (!$this->comptaEcritures->contains($comptaEcriture)) {
            $this->comptaEcritures[] = $comptaEcriture;
            $comptaEcriture->setExercice($this);
        }

        return $this;
    }

    public function removeComptaEcriture(ComptaEcriture $comptaEcriture): self
    {
        if ($this->comptaEcritures->contains($comptaEcriture)) {
            $this->comptaEcritures->removeElement($comptaEcriture);
            // set the owning side to null (unless already changed)
            if ($comptaEcriture->getExercice() === $this) {
                $comptaEcriture->setExercice(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeInterface $created_at): self
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

    public function getDeletedBy(): ?User
    {
        return $this->deleted_by;
    }

    public function setDeletedBy(?User $deleted_by): self
    {
        $this->deleted_by = $deleted_by;

        return $this;
    }

    /**
     * @return Collection|ComptaCompteExercice[]
     */
    public function getComptaCompteExercices(): Collection
    {
        return $this->comptaCompteExercices;
    }

    public function addComptaCompteExercice(ComptaCompteExercice $comptaCompteExercice): self
    {
        if (!$this->comptaCompteExercices->contains($comptaCompteExercice)) {
            $this->comptaCompteExercices[] = $comptaCompteExercice;
            $comptaCompteExercice->setExercice($this);
        }

        return $this;
    }

    public function removeComptaCompteExercice(ComptaCompteExercice $comptaCompteExercice): self
    {
        if ($this->comptaCompteExercices->contains($comptaCompteExercice)) {
            $this->comptaCompteExercices->removeElement($comptaCompteExercice);
            // set the owning side to null (unless already changed)
            if ($comptaCompteExercice->getExercice() === $this) {
                $comptaCompteExercice->setExercice(null);
            }
        }

        return $this;
    }

    public function getAcheve(): ?bool
    {
        return $this->acheve;
    }

    public function setAcheve(bool $acheve): self
    {
        $this->acheve = $acheve;

        return $this;
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
            $customerCommande->setExercice($this);
        }

        return $this;
    }

    public function removeCustomerCommande(CustomerCommande $customerCommande): self
    {
        if ($this->customerCommandes->contains($customerCommande)) {
            $this->customerCommandes->removeElement($customerCommande);
            // set the owning side to null (unless already changed)
            if ($customerCommande->getExercice() === $this) {
                $customerCommande->setExercice(null);
            }
        }

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
            $this->providerCommandes[] = $providerCommande;
            $providerCommande->setExercice($this);
        }

        return $this;
    }

    public function removeProviderCommande(ProviderCommande $providerCommande): self
    {
        if ($this->providerCommandes->contains($providerCommande)) {
            $this->providerCommandes->removeElement($providerCommande);
            // set the owning side to null (unless already changed)
            if ($providerCommande->getExercice() === $this) {
                $providerCommande->setExercice(null);
            }
        }

        return $this;
    }
}
