<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class FonctionsController extends AbstractController
{
    // Cette fonction permet de générer les matricules automatiquement
    public function generateReference(string $type = null, object $object = null, int $nbr = 1)
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
      
      $reference = $object->getReference();
      if(!empty($reference))
      {
        $zero = "";
        $number = (int) substr($reference, $characters_to_delete);
        $numero_ordre = $number + 1;
        if(strlen($numero_ordre) == 1){
          $zero = '00';
        } 
        elseif (strlen($numero_ordre) == 2) {
          $zero = '0';
        }
        $matricule = $prefix.$zero.$numero_ordre;
        if($nbr != 1){
          $multipleMatricules[] = $matricule;
          for ($i=0; $i < $nbr - 1; $i++) { 
            $numero_ordre = $numero_ordre + 1;
            $multipleMatricules[] = $prefix.$zero.$numero_ordre;
          }
          $matricule = $multipleMatricules;
        }
      }
      else{
        $matricule = "PR001";            
      }

      return $matricule;
    }
}
