<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Provider;
use App\Entity\Customer;
use App\Entity\CustomerCommande;
use App\Entity\Product;
use App\Entity\Settlement;
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
        'products'     => $products,
        'statistiques' => $statistiques
      ]);
    }


    /**
     * @Route("/creances-reglees", name="statistiques_creances_reglees")
     */
    public function statistiques_creances_reglees(Request $request, EntityManagerInterface $manager)
    {
      $statistiques = [];
      if($request->isMethod('post')){
        $data = $request->request->all();
        $token = $data['token'];
        if($this->isCsrfTokenValid('statistiques_creances_reglees', $token)){
          $date = new \DateTime($data['date']);
          $commandes = $manager->getRepository(CustomerCommande::class)->creances_reglees($date);
          $statistiques["date"] = $date;
          $creances = [];
          foreach ($commandes as $commande) {
            if(count($commande->getSettlements()) > 1){
              $settlements = $commande->getSettlements();
              foreach ($settlements as $key => $value) {
                if($value->getDate() == $date and $value->getAmount() > 0 and $key != 0){
                  $creances[] = $value;
                }
              }
            }
          }
          
          $statistiques["creances"] = $creances;
        }
        else{
          dd("C'est pas vrai");
        }
      }
      return $this->render('Statistiques/creances-reglees.html.twig', [
        'current'      => 'statistiques',
        'statistiques' => $statistiques
      ]);
    }

    /**
     * @Route("/creances-accordees", name="statistiques_creances_accordees")
     */
    public function statistiques_creances_accordees(Request $request, EntityManagerInterface $manager)
    {
      $statistiques = [];
      if($request->isMethod('post')){
        $data = $request->request->all();
        $token = $data['token'];
        if($this->isCsrfTokenValid('statistiques_creances_accordees', $token)){
          $date                 = new \DateTime($data['date']);
          $commandes            = $manager->getRepository(CustomerCommande::class)->creances_accordees($date);
          $statistiques["date"] = $date;
          $creances             = [];
          $totalCreances        = 0;
          foreach ($commandes as $commande) {
            // $commandeId  = $commande->getId();
            // $settlements = $manager->getRepository(Settlement::class)->versementsAnterieurs1($commandeId, $date);
            if(isset($commande->getSettlements()[0]))
            {
              $settlement = $commande->getSettlements()[0];
              if($settlement->getDate() == $date and $commande->getNetAPayer() > $settlement->getAmount()){
                $creances[] = $settlement;
                $totalCreances = $totalCreances + $commande->getNetAPayer() - $settlement->getAmount();
              }
            }
            // foreach ($settlements as $value) {
            // }
          }
          // dd($totalCreances);
          
          $statistiques["creances"] = $creances;
        }
        else{
          dd("C'est pas vrai");
        }
      }
      return $this->render('Statistiques/creances-accordees.html.twig', [
        'current'      => 'statistiques',
        'statistiques' => $statistiques
      ]);
    }


    /**
     * @Route("/ventes", name="statistiques_ventes")
     */
    public function statistiques_ventes(Request $request, EntityManagerInterface $manager)
    {
      $sells = [];
      $statistiques = [];
      if($request->isMethod('post')){
        $data = $request->request->all();
        $token = $data['token'];
        if($this->isCsrfTokenValid('statistiques_sells', $token)){
          $type = $data["type_vente"];
          $statistiques["type_vente"] = $data["type_vente"];
          $statistiques["debut"] = new \DateTime($data['debut']);
          $statistiques["fin"] = new \DateTime($data['fin']);
          if($type == 1){
            $sells = $manager->getRepository(CustomerCommande::class)->toutes_les_ventes_periode($data['debut'], $data['fin']);
          }
          elseif($type == 2){
            $sells = $manager->getRepository(CustomerCommande::class)->toutes_les_ventes_par_acompte_periode($data['debut'], $data['fin']);
          }
        }
        else{
          dd("C'est pas vrai");
        }
      }
      return $this->render('Statistiques/sells.html.twig', [
        'current'      => 'statistiques',
        'sells'        => $sells,
        'statistiques' => $statistiques
      ]);
    }
}
