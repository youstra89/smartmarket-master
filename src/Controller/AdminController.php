<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/admin")
 */
class AdminController extends AbstractController
{
  /**
   * @Route("/", name="admin")
   * @IsGranted("ROLE_ADMIN")
   */
    public function index()
    {
        return $this->render('base.html.twig', [
          'current' => 'index'
        ]);
    }
}
