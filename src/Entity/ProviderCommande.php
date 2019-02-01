<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProviderCommandeRepository")
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
     * @ORM\OneToOne(targetEntity="App\Entity\Commande", cascade={"persist", "remove"})
     */
    private $commande;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $reference;

    /**
     * @ORM\Column(type="integer")
     */
    private $additional_fees;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Provider", inversedBy="providerOrders")
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

    public function __construct()
    {
        $this->providerCommandeDetails = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }

    public function setCommande(?Commande $commande): self
    {
        $this->commande = $commande;

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
}
