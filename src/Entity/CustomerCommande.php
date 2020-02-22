<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CustomerCommandeRepository")
 * @UniqueEntity("reference", message="Impossible d'ajouter une deuxième vente avec la même référence")
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
     * @ORM\Column(unique=true)
     */
    private $reference;

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
     * @ORM\OneToMany(targetEntity="App\Entity\Settlement", mappedBy="commande")
     */
    private $settlements;

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

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $status;

    public function __construct()
    {
        $this->product          = new ArrayCollection();
        $this->ended            = false;
        $this->is_deleted       = false;
        $this->created_at       = new \DateTime();
        $this->settlements      = new ArrayCollection();
        $this->echeances        = new ArrayCollection();
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

    /**
     * @return Collection|Settlement[]
     */
    public function getSettlements(): Collection
    {
        return $this->settlements;
    }

    public function addSettlement(Settlement $settlement): self
    {
        if (!$this->settlements->contains($settlement)) {
            $this->settlements[] = $settlement;
            $settlement->setCommande($this);
        }

        return $this;
    }

    public function removeSettlement(Settlement $settlement): self
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }
}
