<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class MainController extends AbstractController
{
  /**
   * @Route("/", name="homepage")
   */
    public function index()
    {
        return $this->render('base-user.html.twig');
    }
}
