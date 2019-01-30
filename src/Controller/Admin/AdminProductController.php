<?php
// src/Controller/LuckyController.php
namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * @Route("/admin/products")
 */
class AdminProductController extends AbstractController
{
  /**
   * @Route("/", name="product")
   * @IsGranted("ROLE_ADMIN")
   */
    public function index()
    {
        return $this->render('Admin/Product/index.html.twig', [
          'current' => 'products'
        ]);
    }
}
