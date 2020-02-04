<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FonctionController extends AbstractController
{
    public function flash()
    {
      $this->addFlash('danger', '<li>Désolé ! Votre période d\'essai a expirée.</li> <li>Veuillez nous contacter pour l\'achat de licence.</li> <li>Contacts: 07 31 09 99</li>');
    }

    public function redirection()
    {
      return $this->redirectToRoute('homepage');
    }
}
