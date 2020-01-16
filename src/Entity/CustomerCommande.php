<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CustomerCommandeRepository")
 */
class CustomerCommande
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $reference;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Commande", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $commande;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="customerCommandes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $seller;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Customer", inversedBy="customerCommandes")
     */
    private $customer;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\CustomerCommandeDetails", mappedBy="commande")
     */
    private $product;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Echeance", mappedBy="commande")
     */
    private $echeances;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ReturnedProduct", mappedBy="commande")
     */
    private $returnedProducts;

    public function __construct()
    {
        $this->product = new ArrayCollection();
        $this->echeances = new ArrayCollection();
        $this->returnedProducts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }

    public function setCommande(Commande $commande): self
    {
        $this->commande = $commande;

        return $this;
    }

    public function getSeller(): ?User
    {
        return $this->seller;
    }

    public function setSeller(?User $seller): self
    {
        $this->seller = $seller;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * @return Collection|CustomerCommandeDetails[]
     */
    public function getProduct(): Collection
    {
        return $this->product;
    }

    public function addProduct(CustomerCommandeDetails $product): self
    {
        if (!$this->product->contains($product)) {
            $this->product[] = $product;
            $product->setCustomerCommande($this);
        }

        return $this;
    }

    public function removeProduct(CustomerCommandeDetails $product): self
    {
        if ($this->product->contains($product)) {
            $this->product->removeElement($product);
            // set the owning side to null (unless already changed)
            if ($product->getCustomerCommande() === $this) {
                $product->setCustomerCommande(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Echeance[]
     */
    public function getEcheances(): Collection
    {
        return $this->echeances;
    }

    public function addEcheance(Echeance $echeance): self
    {
        if (!$this->echeances->contains($echeance)) {
            $this->echeances[] = $echeance;
            $echeance->setCommande($this);
        }

        return $this;
    }

    public function removeEcheance(Echeance $echeance): self
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

    /**
     * @return Collection|ReturnedProduct[]
     */
    public function getReturnedProducts(): Collection
    {
        return $this->returnedProducts;
    }

    public function addReturnedProduct(ReturnedProduct $returnedProduct): self
    {
        if (!$this->returnedProducts->contains($returnedProduct)) {
            $this->returnedProducts[] = $returnedProduct;
            $returnedProduct->setCommande($this);
        }

        return $this;
    }

    public function removeReturnedProduct(ReturnedProduct $returnedProduct): self
    {
        if ($this->returnedProducts->contains($returnedProduct)) {
            $this->returnedProducts->removeElement($returnedProduct);
            // set the owning side to null (unless already changed)
            if ($returnedProduct->getCommande() === $this) {
                $returnedProduct->setCommande(null);
            }
        }

        return $this;
    }
}
