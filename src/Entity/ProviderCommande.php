<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProviderCommandeRepository")
 * @UniqueEntity("reference", message="Impossible d'ajouter une deuxième commande avec la même référence")
 */
class ProviderCommande
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $date;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $total_amount;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $ended;

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
     * @ORM\Column(type="string", length=255)
     * @ORM\Column(unique=true)
     */
    private $reference;

    /**
     * @ORM\Column(type="integer")
     */
    private $additional_fees;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Provider", inversedBy="providerCommandes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $provider;

    /**
     * @ORM\Column(type="integer")
     */
    private $transport;

    /**
     * @ORM\Column(type="integer")
     */
    private $dedouanement;

    /**
     * @ORM\Column(type="integer")
     */
    private $currency_cost;

    /**
     * @ORM\Column(type="integer")
     */
    private $forwarding_cost;

    /**
    * @ORM\Column(type="integer")
    */
    private $total_fees;

    /**
    * @ORM\Column(type="integer", nullable=true)
    */
    private $global_total;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ProviderCommandeDetails", mappedBy="commande")
     */
    private $providerCommandeDetails;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ProviderSettlement", mappedBy="commande")
     */
    private $settlements;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ProviderEcheance", mappedBy="commande")
     */
    private $echeances;

    public function __construct()
    {
        $this->ended       = false;
        $this->is_deleted  = false;
        $this->created_at  = new \DateTime();
        $this->echeances   = new ArrayCollection();
        $this->settlements = new ArrayCollection();
        $this->providerCommandeDetails = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTotalCharges()
    {
        return $this->additional_fees + $this->additional_fees + $this->transport + $this->dedouanement + $this->currency_cost;
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

    public function getAdditionalFees(): ?int
    {
        return $this->additional_fees;
    }

    public function setAdditionalFees(int $additional_fees): self
    {
        $this->additional_fees = $additional_fees;

        return $this;
    }

    public function getProvider(): ?Provider
    {
        return $this->provider;
    }

    public function setProvider(?Provider $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function getTransport(): ?int
    {
        return $this->transport;
    }

    public function setTransport(int $transport): self
    {
        $this->transport = $transport;

        return $this;
    }

    public function getDedouanement(): ?int
    {
        return $this->dedouanement;
    }

    public function setDedouanement(int $dedouanement): self
    {
        $this->dedouanement = $dedouanement;

        return $this;
    }

    public function getCurrencyCost(): ?int
    {
        return $this->currency_cost;
    }

    public function setCurrencyCost(int $currency_cost): self
    {
        $this->currency_cost = $currency_cost;

        return $this;
    }

    public function getForwardingCost(): ?int
    {
        return $this->forwarding_cost;
    }

    public function setForwardingCost(int $forwarding_cost): self
    {
        $this->forwarding_cost = $forwarding_cost;

        return $this;
    }

    public function getTotalFees(): ?int
    {
        return $this->total_fees;
    }

    public function setTotalFees(int $total_fees): self
    {
        $this->total_fees = $total_fees;

        return $this;
    }

    public function getGlobalTotal(): ?int
    {
        return $this->global_total;
    }

    public function setGlobalTotal(int $global_total): self
    {
        $this->global_total = $global_total;

        return $this;
    }

    /**
     * @return Collection|ProviderCommandeDetails[]
     */
    public function getProviderCommandeDetails(): Collection
    {
        return $this->providerCommandeDetails;
    }

    public function addProviderCommandeDetail(ProviderCommandeDetails $providerCommandeDetail): self
    {
        if (!$this->providerCommandeDetails->contains($providerCommandeDetail)) {
            $this->providerCommandeDetails[] = $providerCommandeDetail;
            $providerCommandeDetail->setCommande($this);
        }

        return $this;
    }

    public function removeProviderCommandeDetail(ProviderCommandeDetails $providerCommandeDetail): self
    {
        if ($this->providerCommandeDetails->contains($providerCommandeDetail)) {
            $this->providerCommandeDetails->removeElement($providerCommandeDetail);
            // set the owning side to null (unless already changed)
            if ($providerCommandeDetail->getCommande() === $this) {
                $providerCommandeDetail->setCommande(null);
            }
        }

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getTotalAmount(): ?int
    {
        return $this->total_amount;
    }

    public function setTotalAmount(?int $total_amount): self
    {
        $this->total_amount = $total_amount;

        return $this;
    }

    public function getEnded(): ?bool
    {
        return $this->ended;
    }

    public function setEnded(?bool $ended): self
    {
        $this->ended = $ended;

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
     * @return Collection|ProviderSettlement[]
     */
    public function getSettlements(): Collection
    {
        return $this->settlements;
    }

    public function addSettlement(ProviderSettlement $settlement): self
    {
        if (!$this->settlements->contains($settlement)) {
            $this->settlements[] = $settlement;
            $settlement->setCommande($this);
        }

        return $this;
    }

    public function removeSettlement(ProviderSettlement $settlement): self
    {
        if ($this->settlements->contains($settlement)) {
            $this->settlements->removeElement($settlement);
            // set the owning side to null (unless already changed)
            if ($settlement->getCommande() === $this) {
                $settlement->setCommande(null);
            }
        }

        return $this;
    }


    /**
     * @return Collection|ProviderEcheance[]
     */
    public function getEcheances(): Collection
    {
        return $this->echeances;
    }

    public function addEcheance(ProviderEcheance $echeance): self
    {
        if (!$this->echeances->contains($echeance)) {
            $this->echeances[] = $echeance;
            $echeance->setCommande($this);
        }

        return $this;
    }

    public function removeEcheance(ProviderEcheance $echeance): self
    {
        if ($this->echeances->contains($echeance)) {
            $this->echeances->removeElement($echeance);
            // set the owning side to null (unless already changed)
            if ($echeance->getCommande() === $this) {
                $echeance->setCommande(null);
            }
        }

        return $this;
    }
}
