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
     * @ORM\Column(type="string", length=255)
     */
    private $reference;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(min=2, max=255)
     * @ORM\Column(type="string", length=255)
     */
    private $description;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated_at;

    /**
     * @Assert\NotBlank()
     * @ORM\ManyToOne(targetEntity="App\Entity\Category", inversedBy="products")
     */
    private $category;

    /**
     * @Assert\NotBlank()
     * @ORM\ManyToOne(targetEntity="App\Entity\Mark", inversedBy="products")
     */
    private $mark;

    // * @ORM\Column(type="integer", unsigned=true)
    /**
     * @ORM\Column(columnDefinition="integer unsigned")
     */
    private $stock;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ProviderCommandeDetails", mappedBy="product")
     */
    private $providerCommandeDetails;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\CustomerCommandeDetails", mappedBy="product")
     */
    private $customerCommandeDetails;


    public function __construct()
    {
      $this->stock = 0;
      $this->created_at = new \DateTime();
      $this->providerCommandeDetails = new ArrayCollection();
      $this->customerCommandeDetails = new ArrayCollection();
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

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): self
    {
        $this->stock = $stock;

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
            $providerCommandeDetail->setProduct($this);
        }

        return $this;
    }

    public function removeProviderCommandeDetail(ProviderCommandeDetails $providerCommandeDetail): self
    {
        if ($this->providerCommandeDetails->contains($providerCommandeDetail)) {
            $this->providerCommandeDetails->removeElement($providerCommandeDetail);
            // set the owning side to null (unless already changed)
            if ($providerCommandeDetail->getProduct() === $this) {
                $providerCommandeDetail->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|CustomerCommandeDetails[]
     */
    public function getCustomerCommandeDetails(): Collection
    {
        return $this->customerCommandeDetails;
    }

    public function addCustomerCommandeDetail(CustomerCommandeDetails $customerCommandeDetail): self
    {
        if (!$this->customerCommandeDetails->contains($customerCommandeDetail)) {
            $this->customerCommandeDetails[] = $customerCommandeDetail;
            $customerCommandeDetail->setProduct($this);
        }

        return $this;
    }

    public function removeCustomerCommandeDetail(CustomerCommandeDetails $customerCommandeDetail): self
    {
        if ($this->customerCommandeDetails->contains($customerCommandeDetail)) {
            $this->customerCommandeDetails->removeElement($customerCommandeDetail);
            // set the owning side to null (unless already changed)
            if ($customerCommandeDetail->getProduct() === $this) {
                $customerCommandeDetail->setProduct(null);
            }
        }

        return $this;
    }
}
