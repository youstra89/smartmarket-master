<?php
// src/Controller/LuckyController.php
namespace App\Controller\Admin;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Product;
use App\Entity\Customer;
use App\Entity\Settlement;
use App\Entity\CustomerCommande;
use App\Entity\CustomerCommandeSearch;
use App\Entity\CustomerCommandeDetails;
use App\Form\CustomerCommandeSearchType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


/**
 * @Route("/ventes")
 */
class AdminSellController extends AbstractController
{
  /**
   * @Route("/", name="sell")
   * @IsGranted("ROLE_VENTE")
   */
   public function index(Request $request, ObjectManager $manager, PaginatorInterface $paginator)
   {
       $search = new CustomerCommandeSearch();
       $form = $this->createForm(CustomerCommandeSearchType::class, $search);
       $form->handleRequest($request);

       $commandes = $paginator->paginate(
         $manager->getRepository(CustomerCommande::class)->commandesClients($search),
         $request->query->getInt('page', 1),
         20
       );
       return $this->render('Admin/Sell/index.html.twig', [
         'form'      => $form->createView(),
         'current'   => 'sells',
         'commandes' => $commandes
       ]);
   }


  /**
   * @Route("/select-product/{id}", name="get_product")
   * @IsGranted("ROLE_VENTE")
   */
  public function get_product(Request $request, ObjectManager $manager, int $id)
  {
    $product = $manager->getRepository(Product::class)->find($id);
    $data = [
      "id"               => $product->getId(),
      "reference"        => $product->getReference(),
      "label"            => $product->getLabel(),
      "stock"            => $product->getStock(),
      "unit_price"       => $product->getUnitPrice(),
      "purchasing_price" => $product->getPurchasingPrice(),
    ];

    return new JsonResponse($data);
  }

  /**
   * @Route("/select-product-by-reference/{reference}", name="get_product_by_reference")
   * @IsGranted("ROLE_VENTE")
   */
  public function get_product_by_reference(Request $request, ObjectManager $manager, string $reference)
  {
    $product = $manager->getRepository(Product::class)->findByReference($reference);
    $data = [];
    if(!empty($product))
    {
      $data = [
        "id"               => $product[0]->getId(),
        "reference"        => $product[0]->getReference(),
        "label"            => $product[0]->getLabel(),
        "stock"            => $product[0]->getStock(),
        "unit_price"       => $product[0]->getUnitPrice(),
        "purchasing_price" => $product[0]->getPurchasingPrice(),
      ];
    }

    return new JsonResponse($data);
  }

    /**
     * @Route("/unique-form-for-selling", name="unique_form_for_selling")
     * @IsGranted("ROLE_VENTE")
     */
    public function unique_form_for_selling(Request $request, ObjectManager $manager)
    {
        $customers = $manager->getRepository(Customer::class)->findAll();
        $products = $manager->getRepository(Product::class)->findAll();

        if($request->isMethod('post'))
        {
          $data = $request->request->all();
          // return new Response(var_dump($data));
          if(!empty($data['token']))
          {
            $token = $data['token'];
            if($this->isCsrfTokenValid('vente', $token)){
              $data = $request->request->all();
              if(empty($data['date']))
              {
                $this->addFlash('danger', 'Impossible d\'enregistrer une vente sans la date.');
                return $this->redirectToRoute('unique_form_for_selling');
              }
              elseif(empty($data["products"]))
              {
                $this->addFlash('danger', 'Impossible d\'enregistrer une vente sans avoir ajouter des produits.');
                return $this->redirectToRoute('unique_form_for_selling');
              }
              else {
                $date       = new \DateTime($data["date"]);
                $prices     = $data["prices"];
                $quantities = $data["quantities"];
                $seller = $this->getUser();
                $reference = $date->format('Ymd').'.'.(new \DateTime())->format('His');
                $customerCommande = new CustomerCommande();
                if(isset($data['customer']))
                {
                  $customer = (int) $data['customer'];
                  $customer = $manager->getRepository(Customer::class)->find($data['customer']);
                  $customerCommande->setCustomer($customer);
                }
                $customerCommande->setReference($reference);
                $customerCommande->setSeller($seller);
                $customerCommande->setDate($date);
                $customerCommande->setCreatedBy($this->getUser());
                $manager->persist($customerCommande);

                // On va enregistrer les détails de la commande
                // Pour chaque produit de la commande, on doit enregistrer des informations (prix unitaire, qte ...)
                $commandeGlobalCost = 0;
                foreach ($prices as $key => $value) {
                  $product   = $manager->getRepository(Product::class)->find($key);
                  $quantity = $quantities[$key];
                  $subtotal = $value * $quantity;
                  $stockQte  = $product->getStock() - $quantity;

                  if($quantity <= 0)
                  {
                    $this->addFlash('danger', 'Quantité de <strong>'.$product->getLabel().'</strong> incorrecte.');
                    // return new Response(var_dump("Quantité"));
                    return $this->redirectToRoute('unique_form_for_selling');
                  }
                  
                  if($value <= 0)
                  {
                    $this->addFlash('danger', 'Prix de <strong>'.$product->getLabel().'</strong> incorrect.');
                    // return new Response(var_dump("Prix"));
                    return $this->redirectToRoute('unique_form_for_selling');
                  }
                  
                  if($stockQte < 0)
                  {
                    $this->addFlash('danger', 'Quantité de <strong>'.$product->getLabel().'</strong> indisponible en stock.');
                    // return new Response(var_dump("Stock"));
                    return $this->redirectToRoute('unique_form_for_selling');
                  }
                  // On enregistre d'abord les détails de commande
                  $commandeProduit = new CustomerCommandeDetails();
                  $commandeProduit->setCommande($customerCommande);
                  $commandeProduit->setProduct($product);
                  $commandeProduit->setQuantity($quantity);
                  $commandeProduit->setUnitPrice($value);
                  $commandeProduit->setSubtotal($subtotal);
                  $commandeGlobalCost += $subtotal;
                  $manager->persist($commandeProduit);

                  // Ensuite, on met à jour le stock
                  $product->setStock($stockQte);
                  $product->setUpdatedAt(new \DateTime());
                }
                $customerCommande->setTotalAmount($commandeGlobalCost);

                //On va maintenant enregistrer le règlement de la commande
                try{
                  $manager->flush();
                  $this->addFlash('success', '<li>Enregistrement de la vente du <strong>'.$customerCommande->getReference().'</strong> réussie.</li>');
                  // $commandeId = $manager->getRepository(CustomerCommande::class)->findOneByReference($reference)->getId();
                  return $this->redirectToRoute('settlement', ['id' => $customerCommande->getId()]);
                } 
                catch(\Exception $e){
                  $this->addFlash('danger', $e->getMessage());
                  return $this->redirectToRoute('unique_form_for_selling');
                }
              }
            }
          }
        }
        
        return $this->render('Admin/Sell/unique-form-for-selling.html.twig', [
          'current'   => 'sells',
          'products'  => $products,
          'customers' => $customers,
        ]);
    }


    /**
     * @Route("/ajouter-ce-produit-a-la-commande-{id}", name="add.commande.product")
     * @IsGranted("ROLE_VENTE")
     * @param Product $product
     */
    public function add_product_command(Request $request, ObjectManager $manager, Product $product)
    {
        $productId = $product->getId();
        // $commande = $manager->getRepository(Commande::class)->find($commandeId);
        $ids = $this->get('session')->get('idProductsForSelling');
        if($product->getStock() == 0)
        {
          $this->addFlash('warning', '<strong>'.$product->getCategory()->getName().' '.$product->getMark()->getLabel().' - '.$product->getDescription().'</strong> est fini en stock.');
          return $this->redirectToRoute('customer.order.add');
        }
        // On va vérifier la session pour voir si le produit n'est pas déjà sélectionné
        if(!empty($ids)){

          foreach ($ids as $key => $value) {
            if($value === $productId){
              $this->addFlash('warning', '<strong>'.$product->getCategory()->getName().' '.$product->getMark()->getLabel().' - '.$product->getDescription().'</strong> est déjà sélectioné(e).');
              return $this->redirectToRoute('customer.order.add');
            }
          }
        }
        // Append value to retrieved array.
        $ids[] = $productId;
        // Set value back to session
        $this->get('session')->set('idProductsForSelling', $ids);

        $this->addFlash('success', '<strong>'.$product->getCategory()->getName().' '.$product->getMark()->getLabel().' - '.$product->getDescription().'</strong> ajouté(e) à la vente.');
        return $this->redirectToRoute('customer.order.add');
    }

    /**
     * @Route("/annuler-la-vente-en-cours", name="customer.commande.reset")
     * @IsGranted("ROLE_VENTE")
     */
    public function reset_commande(ObjectManager $manager)
    {
        $this->get('session')->remove('idProductsForSelling');
        $this->addFlash('success', 'Vente a été réinitialisée.');
        return $this->redirectToRoute('customer.order.add');
    }


    /**
     * @Route("/reglement-de-vente/{id}", name="settlement", requirements={"id"="\d+"})
     * @IsGranted("ROLE_VENTE")
     * @param CustomerCommande $commande
     */
    public function settlement(Request $request, CustomerCommande $commande, ObjectManager $manager)
    {
      // Lorsque la commande est liée à un client, on cherche tous règlements effectués.
      $reglements = $commande->getSettlements();
      // $total = array_sum(array_map('getValue', $reglements));
      $total = 0;
      foreach ($reglements as $key => $value) {
        $total += $value->getAmount();
      }
      // dump($total);
      $reste = $commande->getTotalAmount() - $total;
      if($request->isMethod('post'))
      {
        $data = $request->request->all();
        $token  = $data['token'];
        $date   = new \DateTime($data['date']);
        $amount = $data['amount'];
        $commandeId = $commande->getId();
        // return new Response(var_dump($data));
        if($this->isCsrfTokenValid('token_reglement', $token)){
          if(empty($date)){
            $this->addFlash('danger', 'Saisir une valuer pour la date.');
            return $this->redirectToRoute('settlement', ['id' => $commandeId]);
          }
          if(empty($amount) or $amount < 0){
            $this->addFlash('danger', 'Montant incorrect. Saisir une valeur supérieure à 0.');
            // return new Response("Montant nul ou négatif");
            return $this->redirectToRoute('settlement', ['id' => $commandeId]);
          }
          if (empty($commande->getCustomer())) {
            if($amount != $commande->getTotalAmount()) {
              $this->addFlash('danger', 'Montant incorrect. La valeur saisie n\'est pas égale au montant total da la commande.');
              // return new Response("Montant différent du total de la commande");
              return $this->redirectToRoute('settlement', ['id' => $commandeId]);
            }
            else {
              $this->addFlash('success', 'Règlement enregistré avec succès. Commande soldée.');
              // return new Response("Règlement Ok sans client");
              $commande->setEnded(true);
            }
          }
          else {
            $newTotal = $amount + $total;
            if($newTotal > $commande->getTotalAmount())
            {
              $this->addFlash('danger', 'Montant incorrect. La somme des règlements est supérieure au montant total da la commande.');
              // return new Response("Somme des règlements supérieure à la commande");
              return $this->redirectToRoute('settlement', ['id' => $commandeId]);
            }
            elseif($newTotal < $commande->getTotalAmount())
            {
              $this->addFlash('success', 'Règlement bien enregistré. Cependant la commande n\'est pas soldée.');
              // return new Response("Règlement Ok, avec dette");
              // return $this->redirectToRoute('sell');
            }
            elseif ($newTotal == $commande->getTotalAmount()) {
              $this->addFlash('success', 'Règlement enregistré avec succès. Commande soldée.');
              // return new Response("Règlement Ok, commande soldée");
              $commande->setEnded(true);
            }
          }
          $user = $this->getUser();
          $settlement = new Settlement();
          $settlement->setDate($date);
          $settlement->setAmount($amount);
          $settlement->setReceiver($user);
          $settlement->setCreatedBy($this->getUser());
          $settlement->setCommande($commande);
          $manager->persist($settlement);
          try{
            $manager->flush();
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          }
          return $this->redirectToRoute('sell');
        }
      }
      return $this->render('Admin/Sell/settlement.html.twig', [
        'current'  => 'sells',
        'reste'    => $reste,
        'commande' => $commande,
      ]);
    }

    function getValue($obj) {
      return $obj -> getAmount();
    }

    /**
     * @Route("/details-vente/{id}", name="customer.order.details")
     * @param CustomerCommande $commande
     * @IsGranted("ROLE_VENTE")
     */
    public function customer_order_details(int $id, ObjectManager $manager, CustomerCommande $commande)
    {
        return $this->render('Admin/Sell/sell-details.html.twig', [
          'current'  => 'sells',
          'commande' => $commande
        ]);
    }

    /**
     * @Route("/facture-de-vente/{id}", name="facture_client")
     * @param CustomerCommande $commande
     * @IsGranted("ROLE_VENTE")
     */
    public function facture_client(CustomerCommande $commande)
    {
        // Configure Dompdf according to your needs
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        
        // Instantiate Dompdf with our options
        $dompdf = new Dompdf($pdfOptions);
        
        // Retrieve the HTML generated in our twig file
        $html = $this->renderView('Admin/Sell/facture.html.twig', [
            'commande'  => $commande
        ]);
        
        // Load HTML to Dompdf
        $dompdf->loadHtml($html);
        //"dompdf/dompdf": "^0.8.3",
        
        // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();

        // Output the generated PDF to Browser (force download)
        $dompdf->stream("mypdf.pdf", [
            "Attachment" => false
        ]);
        return $this->render('Admin/Sell/sell-details.html.twig', [
          'current'  => 'sells',
          'commande' => $commande
        ]);
    }
}
