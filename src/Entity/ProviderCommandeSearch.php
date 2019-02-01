<?php

namespace App\Entity;

use App\Entity\Product;
use App\Entity\Provider;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

class ProviderCommandeSearch
{
  /**
   *@var Provider|null
   */
  private $provider;

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
   *@var Provider|null
   */
  public function getProvider(): ?Provider
  {
    return $this->provider;
  }

  /**
   *@param Provider|null $provider
   *@return ProviderCommandeSearch
   */
  public function setProvider(Provider $provider): ProviderCommandeSearch
  {
    $this->provider = $provider;
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
  //  *@return ProviderCommandeSearch
  //  */
  // public function setMinSurface(int $minSurface): ProviderCommandeSearch
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
