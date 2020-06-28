<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @UniqueEntity("reference")
 * @ORM\Entity(repositoryClass="App\Repository\ProductRepository")
 */
class Product
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(min=3, max=10)
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $reference;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(min=2, max=255)
     * @ORM\Column(type="string", length=255)
     */
    private $description;

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
     * @Assert\NotBlank()
     * @ORM\ManyToOne(targetEntity="App\Entity\Category", inversedBy="products")
     */
    private $category;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Mark", inversedBy="products")
     */
    private $mark;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Family", inversedBy="products")
     */
    private $family;

    /**
     * @ORM\Column(type="integer")
     */
    private $unit_price;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $purchasing_price;

    /**
     * @ORM\Column(type="integer")
     */
    private $security_stock;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $image;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $last_seller;

    /**
     * @ORM\Column(type="integer")
     */
    private $average_purchase_price;

    /**
     * @ORM\Column(type="integer")
     */
    private $unite;

    /**
     * @ORM\Column(type="integer")
     */
    private $average_selling_price;

    /**
     * @ORM\Column(type="integer")
     */
    private $average_package_selling_price;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Stock", mappedBy="product")
     */
    private $stocks;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\DetailsApprovisionnement", mappedBy="product")
     */
    private $detailsApprovisionnements;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\CustomerCommandeDetails", mappedBy="product")
     */
    private $commandeDetails;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $code_barre;


    public function __construct()
    {
        $this->unit_price = 0;
        $this->is_deleted = false;
        $this->created_at = new \DateTime();
        $this->stocks = new ArrayCollection();
        $this->commandeDetails = new ArrayCollection();
        $this->detailsApprovisionnements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function label(): ?string
    {
        $mark = !empty($this->mark) ? $this->mark->getLabel() : '';
        return $this->category->getName().' '.$mark.' '.$this->description;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getMark(): ?Mark
    {
        return $this->mark;
    }

    public function setMark(?Mark $mark): self
    {
        $this->mark = $mark;

        return $this;
    }

    public function getTotalStock(int $typeRetour = 1)
    {
        $total = 0;
        foreach ($this->stocks as $value) {
            $total = $total + $value->getQuantity();
        }
        $qt = $total;
        if ($typeRetour = 2){
            return $qt;
        }

        if ($this->unite !== 1 and $total != 0){
            $nbrProduit = intdiv($total, $this->unite);
            $nbrUnite = $total % $this->unite;
            if($nbrProduit == 0 and $nbrUnite == 0)
                $qt = 0;
            elseif($nbrProduit == 0 and $nbrUnite != 0)
                $qt = $nbrUnite." unités";
            elseif($nbrProduit != 0 and $nbrUnite == 0)
                $qt = $nbrProduit;
            else
                $qt = $nbrProduit."/".$nbrUnite." unités";

        }
        return $qt;
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

    public function getUnitPrice(): ?int
    {
        return $this->unit_price;
    }

    public function setUnitPrice(int $unit_price): self
    {
        $this->unit_price = $unit_price;

        return $this;
    }

    public function getPurchasingPrice(): ?int
    {
        return $this->purchasing_price;
    }

    public function setPurchasingPrice(?int $purchasing_price): self
    {
        $this->purchasing_price = $purchasing_price;

        return $this;
    }

    public function getSecurityStock(): ?int
    {
        return $this->security_stock;
    }

    public function setSecurityStock(int $security_stock): self
    {
        $this->security_stock = $security_stock;

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

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getLastSeller(): ?User
    {
        return $this->last_seller;
    }

    public function setLastSeller(?User $last_seller): self
    {
        $this->last_seller = $last_seller;

        return $this;
    }

    public function getAveragePurchasePrice(): ?int
    {
        return $this->average_purchase_price;
    }

    public function setAveragePurchasePrice(int $average_purchase_price): self
    {
        $this->average_purchase_price = $average_purchase_price;

        return $this;
    }

    public function getUnite(): ?int
    {
        return $this->unite;
    }

    public function setUnite(int $unite): self
    {
        $this->unite = $unite;

        return $this;
    }

    public function getAverageSellingPrice(): ?int
    {
        return $this->average_selling_price;
    }

    public function setAverageSellingPrice(int $average_selling_price): self
    {
        $this->average_selling_price = $average_selling_price;

        return $this;
    }

    public function getAveragePackageSellingPrice(): ?int
    {
        return $this->average_package_selling_price;
    }

    public function setAveragePackageSellingPrice(int $average_package_selling_price): self
    {
        $this->average_package_selling_price = $average_package_selling_price;

        return $this;
    }

    public function getFamily(): ?Family
    {
        return $this->family;
    }

    public function setFamily(?Family $family): self
    {
        $this->family = $family;

        return $this;
    }

    /**
     * @return Collection|Stock[]
     */
    public function getStocks(): Collection
    {
        return $this->stocks;
    }

    public function addStock(Stock $stock): self
    {
        if (!$this->stocks->contains($stock)) {
            $this->stocks[] = $stock;
            $stock->setProduct($this);
        }

        return $this;
    }

    public function removeStock(Stock $stock): self
    {
        if ($this->stocks->contains($stock)) {
            $this->stocks->removeElement($stock);
            // set the owning side to null (unless already changed)
            if ($stock->getProduct() === $this) {
                $stock->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|DetailsApprovisionnement[]
     */
    public function getDetailsApprovisionnements(): Collection
    {
        return $this->detailsApprovisionnements;
    }

    public function addDetailsApprovisionnement(DetailsApprovisionnement $detailsApprovisionnement): self
    {
        if (!$this->detailsApprovisionnements->contains($detailsApprovisionnement)) {
            $this->detailsApprovisionnements[] = $detailsApprovisionnement;
            $detailsApprovisionnement->setProduct($this);
        }

        return $this;
    }

    public function removeDetailsApprovisionnement(DetailsApprovisionnement $detailsApprovisionnement): self
    {
        if ($this->detailsApprovisionnements->contains($detailsApprovisionnement)) {
            $this->detailsApprovisionnements->removeElement($detailsApprovisionnement);
            // set the owning side to null (unless already changed)
            if ($detailsApprovisionnement->getProduct() === $this) {
                $detailsApprovisionnement->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|CustomerCommandeDetails[]
     */
    public function getCommandeDetails(): Collection
    {
        return $this->commandeDetails;
    }

    public function addCommandeDetail(CustomerCommandeDetails $commandeDetail): self
    {
        if (!$this->commandeDetails->contains($commandeDetail)) {
            $this->commandeDetails[] = $commandeDetail;
            $commandeDetail->setProduct($this);
        }

        return $this;
    }

    public function removeCommandeDetail(CustomerCommandeDetails $commandeDetail): self
    {
        if ($this->commandeDetails->contains($commandeDetail)) {
            $this->commandeDetails->removeElement($commandeDetail);
            // set the owning side to null (unless already changed)
            if ($commandeDetail->getProduct() === $this) {
                $commandeDetail->setProduct(null);
            }
        }

        return $this;
    }

    public function getCodeBarre(): ?string
    {
        return $this->code_barre;
    }

    public function setCodeBarre(?string $code_barre): self
    {
        $this->code_barre = $code_barre;

        return $this;
    }
}
