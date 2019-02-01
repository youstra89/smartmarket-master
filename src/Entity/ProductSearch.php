<?php

namespace App\Entity;

use App\Entity\Mark;
use App\Entity\Category;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

class ProductSearch
{
  /**
   *@var Category|null
   */
  private $category;

  /**
   *@var Mark|null
   */
  private $mark;

  /**
   *@var string|null
   */
  private $description;


  /**
   *@var Category|null
   */
  public function getCategory(): ?Category
  {
    return $this->category;
  }

  /**
   *@param Category|null $category
   *@return ProductSearch
   */
  public function setCategory(Category $category): ProductSearch
  {
    $this->category = $category;
    return $this;
  }

  /**
   *@var Mark|null
   */
  public function getMark(): ?Mark
  {
    return $this->mark;
  }

  /**
   *@param Mark|null $mark
   *@return ProductSearch
   */
  public function setMark(Mark $mark): ProductSearch
  {
    $this->mark = $mark;
    return $this;
  }

  /**
   *@var string|null
   */
  public function getDescription(): ?string
  {
    return $this->description;
  }

  /**
   *@param string|null $description
   *@return ProductSearch
   */
  public function setDescription(string $description): ProductSearch
  {
    $this->description = $description;
    return $this;
  }

}
