<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Provider;
use App\Entity\Customer;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/admin/statistiques")
 * @IsGranted("ROLE_STATISTIQUES")
 */
class StatistiquesController extends AbstractController
{
    /**
     * @Route("/", name="statistiques")
     */
    public function index(EntityManagerInterface $manager)
    {
      // $approvisionnements = $manager->getRepository(Approvisionnement::class)->findAll();
      return $this->render('Statistiques/index.html.twig', [
        'current' => 'statistiques',
      ]);
    }


    /**
     * @Route("/clients", name="statistiques_customers")
     */
    public function statistiques_customers(Request $request, EntityManagerInterface $manager)
    {
      $statistiques = [];
      $customers = $manager->getRepository(Customer::class)->findAll();
      // $approvisionnements = $manager->getRepository(Approvisionnement::class)->findAll();
      if($request->isMethod('post')){
        $data = $request->request->all();
        $token = $data['token'];
        if($this->isCsrfTokenValid('statistiques_customers', $token)){
          $customer = $manager->getRepository(Customer::class)->find($data['customer']);
          $statistiques["customer"] = $customer;
          $statistiques["debut"] = new \DateTime($data['debut']);
          $statistiques["fin"] = new \DateTime($data['fin']);
        }
        else{
          dd("C'est pas vrai");
        }
      }
      return $this->render('Statistiques/customers.html.twig', [
        'current'      => 'statistiques',
        'customers'    => $customers,
        'statistiques' => $statistiques
      ]);
    }


    /**
     * @Route("/produits", name="statistiques_products")
     */
    public function statistiques_products(Request $request, EntityManagerInterface $manager)
    {
      $statistiques = [];
      $products = $manager->getRepository(Product::class)->findAll();
      // $approvisionnements = $manager->getRepository(Approvisionnement::class)->findAll();
      if($request->isMethod('post')){
        $data = $request->request->all();
        $token = $data['token'];
        if($this->isCsrfTokenValid('statistiques_products', $token)){
          $product = $manager->getRepository(Product::class)->find($data['product']);
          $statistiques["product"] = $product;
          $statistiques["debut"] = new \DateTime($data['debut']);
          $statistiques["fin"] = new \DateTime($data['fin']);
        }
        else{
          dd("C'est pas vrai");
        }
      }
      return $this->render('Statistiques/products.html.twig', [
        'current'      => 'statistiques',
        'products'    => $products,
        'statistiques' => $statistiques
      ]);
    }
}
