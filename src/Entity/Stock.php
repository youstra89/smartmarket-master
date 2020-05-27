<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\StockRepository")
 */
class Stock
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Product", inversedBy="stocks")
     * @ORM\JoinColumn(nullable=false)
     */
    private $product;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Store", inversedBy="stocks")
     * @ORM\JoinColumn(nullable=false)
     */
    private $store;

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
     * @ORM\Column(type="boolean")
     */
    private $is_root;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deleted_at;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $deleted_by;

    /**
     * @ORM\Column(type="float")
     */
    private $quantity;

    public function __construct()
    {
        $this->is_root    = 0;
        $this->quantity   = 0;
        $this->is_deleted = false;
        $this->created_at = new \DateTime();
    }

    public function getStockUnite()
    {
        $stock = $this->quantity;
        // On va personnaliser cette méthode.
        // Si le produit peut être décomposé en unité, alors on va donner le stock en tenant compte des unités.
        if ($this->product->getUnite() !== 1 and $this->quantity != 0){
            $nbrProduit = intdiv($this->quantity, $this->product->getUnite());
            $nbrUnite = $this->quantity % $this->product->getUnite();
            if($nbrProduit == 0 and $nbrUnite == 0)
                $stock = 0;
            elseif($nbrProduit == 0 and $nbrUnite != 0)
                $stock = $nbrUnite." unités";
            elseif($nbrProduit != 0 and $nbrUnite == 0)
                $stock = $nbrProduit;
            else
                $stock = $nbrProduit."/".$nbrUnite." unités";

        }
        return $stock;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getStore(): ?Store
    {
        return $this->store;
    }

    public function setStore(?Store $store): self
    {
        $this->store = $store;

        return $this;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setQuantity(float $quantity): self
    {
        $this->quantity = $quantity;

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

    public function getIsRoot(): ?bool
    {
        return $this->is_root;
    }

    public function setIsRoot(bool $is_root): self
    {
        $this->is_root = $is_root;

        return $this;
    }
}
