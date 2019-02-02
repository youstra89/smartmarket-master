<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

class ChangePWD
{
  /**
   *@var string|null
   */
  private $password;

  /**
   *@var string|null
   */
  private $new_password;

  /**
   *@var string|null
   */
  private $new_password1;


  /**
   *@var string|null
   */
  public function getPassword(): ?string
  {
    return $this->password;
  }

  /**
   *@param string|null $password
   */
  public function setPassword(string $password): self
  {
    $this->password = $password;
    return $this;
  }

  /**
   *@var string|null
   */
  public function getNewPassword(): ?string
  {
    return $this->new_password;
  }

  /**
   *@param string|null $new_password
   */
  public function setNewPassword(string $new_password): self
  {
    $this->new_password = $new_password;
    return $this;
  }

  /**
   *@var string|null
   */
  public function getNewPassword1(): ?string
  {
    return $this->new_password1;
  }

  /**
   *@param string|null $new_password1
   */
  public function setNewPassword1(string $new_password1): self
  {
    $this->new_password1 = $new_password1;
    return $this;
  }

}
