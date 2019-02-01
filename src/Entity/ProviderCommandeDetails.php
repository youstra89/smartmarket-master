<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProviderCommandeDetailsRepository")
 */
class ProviderCommandeDetails
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $unit_price;

    /**
     * @ORM\Column(type="integer")
     */
    private $quantity;

    /**
     * @ORM\Column(type="integer")
     */
    private $minimum_selling_price;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $fixed_amount;

    /**
     * @ORM\Column(type="integer")
     */
    private $subtotal;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Product", inversedBy="providerCommandeDetails")
     * @ORM\JoinColumn(nullable=false)
     */
    private $product;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ProviderCommande", inversedBy="providerCommandeDetails")
     * @ORM\JoinColumn(nullable=false)
     */
    private $commande;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUnitPrice(): ?int
    {
        return $this->unit_price;
    }

    public function setUnitPrice(int $unit_price): self
    {
        $this->unit_price = $unit_price;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getMinimumSellingPrice(): ?int
    {
        return $this->minimum_selling_price;
    }

    public function setMinimumSellingPrice(int $minimum_selling_price): self
    {
        $this->minimum_selling_price = $minimum_selling_price;

        return $this;
    }

    public function getFixedAmount(): ?int
    {
        return $this->fixed_amount;
    }

    public function setFixedAmount(int $fixed_amount): self
    {
        $this->fixed_amount = $fixed_amount;

        return $this;
    }

    public function getSubtotal(): ?int
    {
        return $this->subtotal;
    }

    public function setSubtotal(int $subtotal): self
    {
        $this->subtotal = $subtotal;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getCommande(): ?ProviderCommande
    {
        return $this->commande;
    }

    public function setCommande(?ProviderCommande $commande): self
    {
        $this->commande = $commande;

        return $this;
    }
}
