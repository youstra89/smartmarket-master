<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\Provider;
use App\Entity\Product;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class FonctionsController extends AbstractController
{
    // Cette fonction permet de générer les matricules automatiquement
    public function generateReference(string $type = null, object $object = null)
    {
      $prefix = "";
      $characters_to_delete = 2;
      if($type == 'product'){
        $prefix = "PR";
      }
      elseif($type == 'customer'){
        $prefix = "CLT";
        $characters_to_delete = 3;
      }
      elseif($type == 'provider'){
        $prefix = "FO";
      }
      
      if(!empty($object))
      {
        $zero = "";
        $number = (int) substr($object->getReference(), $characters_to_delete);
        $numero_ordre = $number + 1;
        if(strlen($numero_ordre) == 1){
          $zero = '00';
        } 
        elseif (strlen($numero_ordre) == 2) {
          $zero = '0';
        }
        $matricule = $prefix.$zero.$numero_ordre;
      }
      else{
        $matricule = "PR001";            
      }
      return $matricule;
    }
}
