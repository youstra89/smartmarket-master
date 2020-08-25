<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MainController extends AbstractController
{
  /**
   * @Route("/", name="homepage")
   */
    public function index()
    {
      $user = $this->getUser();
      if(empty($user))
        return $this->redirectToRoute('login');
      $hasAccess = $this->isGranted('ROLE_SUPER_ADMIN');
      // if ($this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN'))
      if($hasAccess)
          return $this->redirectToRoute('admin');
      return $this->render('base.html.twig', ["current" => "index"]);
    }
}
