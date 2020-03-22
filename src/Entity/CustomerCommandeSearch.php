<?php

namespace App\Entity;

use App\Entity\Product;
use App\Entity\Customer;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

class CustomerCommandeSearch
{
  /**
   *@var Customer|null
   */
  private $customer;

  /**
   * Undocumented variable
   *
   * @var string|null
   */
  private $reference;

  // /**
  //  *@var int|null
  //  *@Assert\Range(min=10, max=400)
  //  */
  // private $minSurface;


  /**
   *@var ArrayCollection
   *
   */
  private $products;

  public function __construct()
  {
    $this->products = new ArrayCollection();
  }


  /**
   *@var Customer|null
   */
  public function getCustomer(): ?Customer
  {
    return $this->customer;
  }

  /**
   *@param Customer|null $customer
   *@return CustomerCommandeSearch
   */
  public function setCustomer(Customer $customer): CustomerCommandeSearch
  {
    $this->customer = $customer;
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

  // /**
  //  *@var int|null
  //  */
  // public function getMinSurface(): ?int
  // {
  //   return $this->minSurface;
  // }
  //
  // /**
  //  *@param int|null $minSurface
  //  *@return CustomerCommandeSearch
  //  */
  // public function setMinSurface(int $minSurface): CustomerCommandeSearch
  // {
  //   $this->minSurface = $minSurface;
  //   return $this;
  // }
  //
  /**
   *@return ArrayCollection
   */
  public function getProducts(): ArrayCollection
  {
    return $this->products;
  }

  /**
   *@param ArrayCollection $products
   */
  public function setProducts(ArrayCollection $products): void
  {
    $this->products = $products;
  }

}
