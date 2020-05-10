<?php
// src/Controller/LuckyController.php
namespace App\Controller\Admin;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Product;
use App\Entity\Customer;
use App\Entity\Settlement;
use App\Entity\Informations;
use App\Entity\CustomerCommande;
use App\Entity\CustomerCommandeSearch;
use App\Entity\CustomerCommandeDetails;
use App\Form\CustomerCommandeSearchType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\Collection;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Controller\FonctionsComptabiliteController;
use App\Entity\ComptaExercice;
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
  public function index(Request $request, EntityManagerInterface $manager, PaginatorInterface $paginator)
  {
    // dd($_SERVER['HTTP_USER_AGENT']);
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
   * @Route("/preparing-sells", name="preparing_sells")
   * @IsGranted("ROLE_VENTE")
   */
  public function preparing_sells(Request $request, EntityManagerInterface $manager, PaginatorInterface $paginator)
  {
    $search = new CustomerCommandeSearch();
    $form   = $this->createForm(CustomerCommandeSearchType::class, $search);
    $form->handleRequest($request);

    $commandes = $paginator->paginate(
      $manager->getRepository(CustomerCommande::class)->commandesClientsAPreparer($search),
      $request->query->getInt('page', 1),
      20
    );

    return $this->render('Admin/Sell/preparing-sells.html.twig', [
      'form'      => $form->createView(),
      'current'   => 'sells',
      'commandes' => $commandes
    ]);
  }


  /**
   * @Route("/select-product/{id}", name="get_product")
   * @IsGranted("ROLE_VENTE")
   */
  public function get_product(Request $request, EntityManagerInterface $manager, int $id)
  {
    $product = $manager->getRepository(Product::class)->find($id);
    $data = [
      "id"               => $product->getId(),
      "reference"        => $product->getReference(),
      "label"            => $product->label(),
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
  public function get_product_by_reference(Request $request, EntityManagerInterface $manager, string $reference)
  {
    $product = $manager->getRepository(Product::class)->findByReference($reference);
    $data = [];
    if(!empty($product))
    {
      $data = [
        "id"               => $product[0]->getId(),
        "reference"        => $product[0]->getReference(),
        "label"            => $product[0]->label(),
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
    public function unique_form_for_selling(Request $request, EntityManagerInterface $manager, FonctionsComptabiliteController $fonctions)
    {
      $customers = $manager->getRepository(Customer      ::class)->findAll();
      $products  = $manager->getRepository(Product       ::class)->findAll();
      $exercice  = $manager->getRepository(ComptaExercice::class)->dernierExerciceEnCours();

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
            else
            {
              $date = new \DateTime($data["date"]);
              if($date < $exercice->getDateDebut() or $date > $exercice->getDateFin()){
                $this->addFlash('danger', 'Impossible de continuer. La date saisie ne fait pas partie de la période d\'exercice en cours.');
                return $this->redirectToRoute('unique_form_for_selling');
              }
            }
            if(empty($data["customer"]))
            {
              $this->addFlash('danger', 'Impossible d\'enregistrer une vente sans client.');
              return $this->redirectToRoute('unique_form_for_selling');
            }
            if(empty($data["products"]))
            {
              $this->addFlash('danger', 'Impossible d\'enregistrer une vente sans avoir ajouter des produits.');
              return $this->redirectToRoute('unique_form_for_selling');
            }
            else {
              $date       = new \DateTime($data["date"]);
              $prices     = $data["prices"];
              $tva        = $data["tva"];
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
              $customerCommande->setExercice($exercice);
              $customerCommande->setSeller($seller);
              $customerCommande->setDate($date);
              $customerCommande->setTva($tva);
              $customerCommande->setStatus("LIVREE");
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
                  $this->addFlash('danger', 'Quantité de <strong>'.$product->label().'</strong> incorrecte.');
                  // return new Response(var_dump("Quantité"));
                  return $this->redirectToRoute('unique_form_for_selling');
                }
                
                if($value <= 0)
                {
                  $this->addFlash('danger', 'Prix de <strong>'.$product->label().'</strong> incorrect.');
                  // return new Response(var_dump("Prix"));
                  return $this->redirectToRoute('unique_form_for_selling');
                }
                
                if($stockQte < 0)
                {
                  $this->addFlash('danger', 'Quantité de <strong>'.$product->label().'</strong> indisponible en stock.');
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
                $commandeProduit->setCreatedBy($this->getUser());
                $commandeGlobalCost += $subtotal;
                $manager->persist($commandeProduit);

                // Ensuite, on met à jour le stock
                $product->setStock($stockQte);
                $product->setLastSeller($seller);
                $product->setUpdatedAt(new \DateTime());
              }
              $customerCommande->setTotalAmount($commandeGlobalCost);
              $customerCommande->setMontantTtc($commandeGlobalCost + $commandeGlobalCost * ($tva/100));

              //On va maintenant enregistrer le règlement de la commande
              try{
                $manager->flush();
                $this->addFlash('success', '<li>Enregistrement de la vente N°<strong>'.$customerCommande->getReference().'</strong> réussie.</li>');
                $fonctions->EcritureDeVenteDansLeJournalComptable($manager, $commandeGlobalCost, $tva, $exercice, $date, $customerCommande);
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
     * @Route("/edit-sell/{id}", name="edit_sell")
     * @param CustomerCommande $commande
     */
    public function edit_sell(Request $request, EntityManagerInterface $manager, int $id, CustomerCommande $commande, FonctionsComptabiliteController $fonctions)
    {
      if(count($commande->getSettlements()) > 1)
        return $this->redirectToRoute('customer.order.details', ["id" => $id]);
      elseif($commande->getDate()->format('d-m-Y') !== (new \DateTime())->format('d-m-Y'))
        return $this->redirectToRoute('customer.order.details', ["id" => $id]);

      if($request->isMethod('post'))
      {
        $data = $request->request->all();
        // return new Response(var_dump($data));
        if(!empty($data['token']))
        {
          $token = $data['token'];
          if($this->isCsrfTokenValid('modifier_vente', $token)){
            $data       = $request->request->all();
            $quantities = $data["quantitiesH"];
            $prices     = $data["pricesH"];
            $total      = $data["total"];
            $change     = false;
            // Si la somme total des versements effectués est supérieure au montant total de la commande, il faut arrếter le script et modifier les versements
            if($commande->getTotalSettlments() > $total)
            {
              $this->addFlash('danger', 'Impossible de continuer. Car la somme des versements est supérieure au total de commande.');
              return $this->redirectToRoute('edit_sell', ["id" => $id]);
            }

            foreach ($commande->getProduct() as $key => $value) {
              // $product   = $manager->getRepository(Product::class)->find($value->getProduct()->getId());
              $product   = $value->getProduct();
              $productId = $product->getId();
              $quantity  = (int) $quantities[$productId];
              $price     = (int) $prices[$productId];
              if($value->getQuantity() !== $quantity or $value->getUnitPrice() !== $price)
              {
                // dump($product);
                // On va voir s'il y a eu augmentation ou diminution de quantité
                $diff     = $value->getQuantity() - $quantity;

                // Si $diff est négatif, cela signifie qu'il y a eu une augmentation de quantité.
                // Dans ce cas, on va vérifier si la différence demandée est disponible en stock. Si oui, on procède au mises à jour
                if ($diff < 0) {
                  $diff = abs($diff);
                  if($product->getStock() >= $diff)
                    $product->setStock($product->getStock() - $diff);
                  else{
                    $this->addFlash('danger', 'Impossible de satisfaire la demande d\'augmentation. La quantité demandée ('.$diff.') n\'est pas disponible en stock');
                    return $this->redirectToRoute('edit_sell', ["id" => $id]);
                  }
                } 
                elseif ($diff > 0) {
                  $product->setStock($product->getStock() + $diff);
                }
                
                $change   = true;
                $subtotal = $quantity * $price;
                $value->setQuantity($quantity);
                $value->setUnitPrice($price);
                $value->setSubtotal($subtotal);
                $value->setUpdatedAt(new \DateTime());
                $value->setUpdatedBy($this->getUser());
  
              }
            }

            if($change === true)
            {
              $tva = $commande->getTva();
              $ancienTotal = $commande->getTotalAmount();
              $commande->setTotalAmount($total);
              $commande->setMontantTtc($total + $total * ($tva/100));
              $commande->setUpdatedAt(new \DateTime());
              $commande->setUpdatedBy($this->getUser());
              try{
                $manager->flush();
                $fonctions->ecritureDeModificationDeVente($manager, $commande, $ancienTotal);
                $this->addFlash('success', 'La commande N°<strong>'.$commande->getReference().'</strong> du <strong>'.$commande->getDate()->format('d-m-Y').'</strong> à été modifiée avec succès.');
              } 
              catch(\Exception $e){
                $this->addFlash('danger', $e->getMessage());
                return $this->redirectToRoute('edit_sell', ["id" => $id]);
              }
            }
            return $this->redirectToRoute('sell');
          }
          else{
            $this->addFlash('danger', 'Jeton de sécurité non valide.');
          }
        }
      }
      return $this->render('Admin/Sell/sell-edit.html.twig', [
        'current'  => 'sells',
        'commande' => $commande,
      ]);
    }


    /**
     * @Route("/prepare-sell-for-customer/{id}", name="prepare_sell_for_customer", requirements={"id"="\d+"})
     * @IsGranted("ROLE_VENTE")
     * @param Customer $customer
     */
    public function prepare_sell_for_customer(Request $request, EntityManagerInterface $manager, Customer $customer, int $id)
    {
      $products = $manager->getRepository(Product::class)->findAll();
      $exercice = $manager->getRepository(ComptaExercice::class)->dernierExerciceEnCours();

      if($request->isMethod('post'))
      {
        $data = $request->request->all();
        // return new Response(var_dump($data));
        if(!empty($data['token']))
        {
          $token = $data['token'];
          if($this->isCsrfTokenValid('vente', $token)){
            $data = $request->request->all();
            if(empty($data["products"]))
            {
              $this->addFlash('danger', 'Impossible d\'enregistrer une vente sans avoir ajouter des produits.');
              return $this->redirectToRoute('prepare_sell_for_customer');
            }
            else {
              $date       = new \DateTime();
              $tva        = $data["tva"];
              $prices     = $data["prices"];
              $quantities = $data["quantities"];
              $seller     = $this->getUser();
              $reference  = $date->format('Ymd').'.'.(new \DateTime())->format('His');
              $customerCommande = new CustomerCommande();
              $customerCommande->setCustomer($customer);
              $customerCommande->setReference($reference);
              $customerCommande->setExercice($exercice);
              $customerCommande->setSeller($seller);
              $customerCommande->setDate($date);
              $customerCommande->setTva($tva);
              $customerCommande->setCreatedBy($this->getUser());
              $customerCommande->setStatus("ENREGISTREE");
              $manager->persist($customerCommande);

              // On va enregistrer les détails de la commande
              // Pour chaque produit de la commande, on doit enregistrer des informations (prix unitaire, qte ...)
              $commandeGlobalCost = 0;
              foreach ($prices as $key => $value) {
                $product   = $manager->getRepository(Product::class)->find($key);
                $quantity = $quantities[$key];
                $subtotal = $value * $quantity;

                if($quantity <= 0)
                {
                  $this->addFlash('danger', 'Quantité de <strong>'.$product->label().'</strong> incorrecte.');
                  // return new Response(var_dump("Quantité"));
                  return $this->redirectToRoute('prepare_sell_for_customer', ["id" => $id]);
                }
                
                if($value <= 0)
                {
                  $this->addFlash('danger', 'Prix de <strong>'.$product->label().'</strong> incorrect.');
                  // return new Response(var_dump("Prix"));
                  return $this->redirectToRoute('prepare_sell_for_customer', ["id" => $id]);
                }
                
                // On enregistre d'abord les détails de commande
                $commandeProduit = new CustomerCommandeDetails();
                $commandeProduit->setCommande($customerCommande);
                $commandeProduit->setProduct($product);
                $commandeProduit->setQuantity($quantity);
                $commandeProduit->setUnitPrice($value);
                $commandeProduit->setSubtotal($subtotal);
                $commandeProduit->setCreatedBy($this->getUser());
                $commandeGlobalCost += $subtotal;
                $manager->persist($commandeProduit);
              }
              $customerCommande->setTotalAmount($commandeGlobalCost);
              $customerCommande->setMontantTtc($commandeGlobalCost + $commandeGlobalCost * ($tva/100));

              //On va maintenant enregistrer le règlement de la commande
              try{
                $manager->flush();
                $this->addFlash('success', '<li>Enregistrement de la commande client N° <strong>'.$customerCommande->getReference().'</strong> réussie.</li>');
                // $commandeId = $manager->getRepository(CustomerCommande::class)->findOneByReference($reference)->getId();
                return $this->redirectToRoute('preparing_sells');
              } 
              catch(\Exception $e){
                $this->addFlash('danger', $e->getMessage());
                return $this->redirectToRoute('prepare_sell_for_customer', ["id" => $id]);
              }
            }
          }
        }
      }
      
      return $this->render('Admin/Sell/prepare-sell-for-customer.html.twig', [
        'current'  => 'sells',
        'products' => $products,
        'customer' => $customer,
      ]);
    }

    /**
     * @Route("/deliver-customer-commande/{id}", name="deliver_customer_commande", requirements={"id"="\d+"})
     * @param CustomerCommande $commande
     */
    public function deliver_customer_commande(Request $request, EntityManagerInterface $manager, CustomerCommande $commande, int $id)
    {
      $livraisonPossible = true;
      $products = $commande->getProduct();
      $stocks = $this->verification_stock_produits($manager, $products);
      // dump($stocks);
      foreach ($stocks as $key => $value) {
        if($value["disponibilite"] == false)
         $livraisonPossible = false;
      }

      if($request->isMethod('post'))
      {
        $data = $request->request->all();
        // dd($commande);
        // return new Response(var_dump($data));
        if(!empty($data['token']))
        {
          $token = $data['token'];
          if($this->isCsrfTokenValid('vente', $token)){}
          $commande->setUpdatedBy($this->getUser());
          $commande->setUpdatedAt(new \DateTime());
          $commande->setStatus("LIVREE");

          foreach ($products as $key => $value) {
            $productId = $value->getProduct()->getId();
            $product = $manager->getRepository(Product::class)->find($productId);
            $newStock = $product->getStock() - $value->getQuantity();
            $product->setStock($newStock);
          }

          //On va maintenant enregistrer le règlement de la commande
          try{
            $manager->flush();
            $this->addFlash('success', '<li>Livraison de la commande client N° <strong>'.$commande->getReference().'</strong> effectuée.</li>');
            // $commandeId = $manager->getRepository(CustomerCommande::class)->findOneByReference($reference)->getId();
            return $this->redirectToRoute('settlement', ["id" => $id]);
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('deliver_customer_commande', ["id" => $id]);
          }
        }
      }

      return $this->render('Admin/Sell/deliver-customer-commande.html.twig', [
        'stocks'   => $stocks,
        'current'  => 'sells',
        'commande' => $commande,
        'livraisonPossible' => $livraisonPossible,
      ]);
    }

    public function verification_stock_produits(EntityManagerInterface $manager, Collection $products)
    {
      $stocks = [];
      foreach ($products as $key => $value) {
        $productId = $value->getProduct()->getId();
        $item = $manager->getRepository(Product::class)->find($productId);
        $stocks[$productId]["stock"] = $item->getStock();
        $stocks[$productId]["disponibilite"] = $item->getStock() >= $value->getQuantity() ? true : false;
      }

      return $stocks;
    }

    /**
     * @Route("/annuler-la-vente-en-cours", name="customer.commande.reset")
     * @IsGranted("ROLE_VENTE")
     */
    public function reset_commande()
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
    public function settlement(Request $request, int $id, CustomerCommande $commande, EntityManagerInterface $manager, FonctionsComptabiliteController $fonctions)
    {
      if ($commande->getEnded() == true) {
        $this->addFlash('warning', 'Cette commande est déjà soldée.');
        return $this->redirectToRoute('sell');
      }
      $exercice  = $manager->getRepository(ComptaExercice::class)->dernierExerciceEnCours();
      // Lorsque la commande est liée à un client, on cherche tous règlements effectués.
      $reglements = $commande->getSettlements();
      // $total = array_sum(array_map('getValue', $reglements));
      $total = 0;
      foreach ($reglements as $key => $value) {
        $total += $value->getAmount();
      }
      // dump($total);
      $reste = $commande->getMontantTtc() - $total;
      if($request->isMethod('post'))
      {
        $data = $request->request->all();
        $token  = $data['token'];
        $date   = new \DateTime($data['date']);
        $mode   = (int) $data['mode'];
        $amount = (int) $data['amount'];
        $soldee = false;
        if($this->isCsrfTokenValid('token_reglement', $token)){
          if(empty($date)){
            $this->addFlash('danger', 'Saisir une valuer pour la date.');
            return $this->redirectToRoute('settlement', ['id' => $id]);
          }
          else
          {
            $date = new \DateTime($data["date"]);
            if($date < $exercice->getDateDebut() or $date > $exercice->getDateFin()){
              $this->addFlash('danger', 'Impossible de continuer. La date saisie ne fait pas partie de la période d\'exercice en cours.');
              return $this->redirectToRoute('settlement', ['id' => $id]);
            }
          }
          if(empty($amount) or $amount < 0){
            $this->addFlash('danger', 'Montant incorrect. Saisir une valeur supérieure à 0.');
            return $this->redirectToRoute('settlement', ['id' => $id]);
          }
          if (empty($commande->getCustomer())) {
            if($amount != $commande->getMontantTtc()) {
              $this->addFlash('danger', 'Montant incorrect. La valeur saisie n\'est pas égale au montant total da la commande.');
              return $this->redirectToRoute('settlement', ['id' => $id]);
            }
            else {
              $this->addFlash('success', 'Règlement enregistré avec succès. Commande soldée.');
              $commande->setEnded(true);
            }
          }
          else {
            $newTotal = $amount + $total;
            $dernierVersement = $manager->getRepository(Settlement::class)->lastSettlement($id);
            if(!empty($dernierVersement) and $dernierVersement->getDate() > $date)
            {
              $this->addFlash('danger', 'Impossible d\'enregistrer ce versement car la date est antérieure au dernier versement ('. $dernierVersement->getDate()->format('d-m-Y') .').');
              return $this->redirectToRoute('settlement', ['id' => $id]);
            }
            elseif($newTotal > $commande->getMontantTtc())
            {
              $this->addFlash('danger', 'Montant incorrect. La somme des règlements est supérieure au montant total da la commande.');
              return $this->redirectToRoute('settlement', ['id' => $id]);
            }
            elseif($newTotal < $commande->getMontantTtc())
            {
              $soldee = false;
            }
            elseif ($newTotal == $commande->getMontantTtc()) {
              $soldee = true;
              $commande->setEnded(true);
            }
          }
          // dd($request->attributes->get('from'));
          $user = $this->getUser();
          $reference = $this->generateInvoiceReference($manager);
          $settlementNumber = $this->generateSettlementNumber(empty($dernierVersement) ? null : $dernierVersement);
          $settlement = new Settlement();
          $settlement->setDate($date);
          $settlement->setReference($reference);
          $settlement->setModePaiement($mode);
          $settlement->setAmount($amount);
          $settlement->setNumber($settlementNumber);
          $settlement->setReceiver($user);
          $settlement->setCreatedBy($this->getUser());
          $settlement->setCommande($commande);
          $manager->persist($settlement);
          try{
            if ($soldee == true)
              $this->addFlash('success', 'Règlement enregistré avec succès. Commande soldée.');
            else
              $this->addFlash('success', 'Règlement bien enregistré. Cependant la commande n\'est pas soldée.');
            
            $manager->flush();
            $fonctions->EcritureDeReglementsClientsDansLeJournalComptable($manager, $mode, $amount, $exercice, $date, $settlement);
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          }
          return $this->redirectToRoute('customer.order.details', ['id' => $id]);
          // if(empty($data['from']))
          // else
          //   return $this->redirectToRoute('accounting.debtor');
        }
      }
      return $this->render('Admin/Sell/settlement.html.twig', [
        'current'  => 'sells',
        'reste'    => $reste,
        'commande' => $commande,
      ]);
    }


    /**
     * @Route("/edition-d-un-reglement-de-vente/{id}", name="edit_settlement", requirements={"id"="\d+"})
     * @IsGranted("ROLE_VENTE")
     * @param Settlement $settlement
     */
    public function edit_settlement(Request $request, int $id, Settlement $settlement, EntityManagerInterface $manager)
    {
      $commande   = $settlement->getCommande();
      $commandeId = $commande->getId();
      $dernierVersement = $manager->getRepository(Settlement::class)->lastSettlement($commandeId);
      if ($dernierVersement !== $settlement) {
        $this->addFlash('danger', 'Vous ne pouvez modifier que le dernier versement.');
        return $this->redirectToRoute('customer.order.details', ['id' => $commandeId]);
      }
      // elseif ($commande->getEnded() === true) {
      //   $this->addFlash('danger', 'Impossible de continuer, car cette commande est déjà soldée.');
      //   return $this->redirectToRoute('customer.order.details', ['id' => $commandeId]);
      // }
      if($request->isMethod('post'))
      {
        $reglements = $commande->getSettlements();
        // $total = array_sum(array_map('getValue', $reglements));
        $total = 0;
        foreach ($reglements as $key => $value) {
          if ($id !== $value->getId()) {
            $total += $value->getAmount();
          }
        }
        $data   = $request->request->all();
        $token  = $data['token'];
        $date   = new \DateTime($data['date']);
        $amount = $data['amount'];
        // return new Response(var_dump($data));
        if($this->isCsrfTokenValid('token_edit_reglement', $token)){
          $soldee = false;
          if(empty($date)){
            $this->addFlash('danger', 'Saisir une valuer pour la date.');
            return $this->redirectToRoute('customer.order.details', ['id' => $commandeId]);
          }
          if(empty($amount) or $amount < 0){
            $this->addFlash('danger', 'Montant incorrect. Saisir une valeur supérieure à 0.');
            // return new Response("Montant nul ou négatif");
            return $this->redirectToRoute('customer.order.details', ['id' => $commandeId]);
          }

          $newTotal = $amount + $total;
          if(!empty($dernierVersement) and $dernierVersement->getDate() > $date)
          {
            $this->addFlash('danger', 'Impossible d\'enregistrer ce versement car la date est antérieure au dernier versement ('. $dernierVersement->getDate()->format('d-m-Y') .').');
            return $this->redirectToRoute('customer.order.details', ['id' => $commandeId]);
          }
          elseif($newTotal > $commande->getMontantTtc())
          {
            $this->addFlash('danger', 'Montant incorrect. La somme des règlements est supérieure au montant total da la commande.');
            return $this->redirectToRoute('customer.order.details', ['id' => $commandeId]);
          }
          elseif($newTotal < $commande->getMontantTtc())
          {
            $commande->setEnded(false);
          }
          elseif ($newTotal == $commande->getMontantTtc()) {
            $soldee = true;
            $commande->setEnded(true);
          }
          $settlement->setDate($date);
          $settlement->setAmount($amount);
          $settlement->setUpdatedAt(new \DateTime());
          $settlement->setUpdatedBy($this->getUser());
          try{
            if ($soldee == true)
              $this->addFlash('success', 'Règlement mise à jour avec succès. Commande soldée.');
            else
              $this->addFlash('success', 'Règlement bien mise à jour. Cependant la commande n\'est pas soldée.');
            $manager->flush();
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          }
          return $this->redirectToRoute('customer.order.details', ['id' => $commandeId]);
        }
      }
      return $this->render('Admin/Sell/edit-settlement.html.twig', [
        'current'  => 'sells',
        'settlement' => $settlement,
      ]);
    }


    function getValue($obj) {
      return $obj -> getAmount();
    }

    public function generateInvoiceReference(EntityManagerInterface $manager)
    {
      $lastNumber = $manager->getRepository(Settlement::class)->lastNumber();
      $reference = $lastNumber.'-'.(new \DateTime())->format("His-dmY");
      return $reference;
    }

    public function generateSettlementNumber($settlement)
    {
      if (empty($settlement)) {
        $number = 1;
      } 
      else {
        $number = $settlement->getNumber() + 1;
      }
      
      return $number;
    }

    /**
     * @Route("/details-vente/{id}", name="customer.order.details")
     * @param CustomerCommande $commande
     * @IsGranted("ROLE_VENTE")
     */
    public function customer_order_details(CustomerCommande $commande)
    {
      if ($commande->getIsDeleted() === true) {
        # code...
        $this->addFlash('error', 'Commande client inexistante.');
        return $this->redirectToRoute('sell');
      }

      return $this->render('Admin/Sell/sell-details.html.twig', [
        'current'  => 'sells',
        'commande' => $commande
      ]);
    }

    /**
     * @Route("/details-commande-client/{id}", name="customer_commande_details")
     * @param CustomerCommande $commande
     * @IsGranted("ROLE_VENTE")
     */
    public function customer_commande_details(CustomerCommande $commande)
    {
      if ($commande->getIsDeleted() === true) {
        # code...
        $this->addFlash('error', 'Commande client inexistante.');
        return $this->redirectToRoute('preparing_sells');
      }

      return $this->render('Admin/Sell/commande-details.html.twig', [
        'current'  => 'sells',
        'commande' => $commande
      ]);
    }

    /**
   * @Route("/delete-commande/{id}", name="delete_commande", methods="GET|POST", requirements={"id"="\d+"})
   * @param CustomerCommande $commande
   */
  public function delete_commande(Request $request, EntityManagerInterface $manager, CustomerCommande $commande, int $id)
  {
    $token = $request->get('_csrf_token');
    if($this->isCsrfTokenValid('delete_commande', $token))
    {
      $commande->setIsDeleted(true);
      $commande->setDeletedBy($this->getUser());
      $commande->setDeletedAt(new \DateTime());

      // On va supprimer les règlements liés à la commande
      if (!empty($commande->getSettlements())) {
        foreach ($commande->getSettlements() as $key => $value) {
          $value->setIsDeleted(true);
          $value->setDeletedBy($this->getUser());
          $value->setDeletedAt(new \DateTime());
        }
      }

      // On en fait de même pour les échéances
      if (!empty($commande->getEcheances())) {
        foreach ($commande->getEcheances() as $key => $value) {
          $value->setIsDeleted(true);
          $value->setDeletedBy($this->getUser());
          $value->setDeletedAt(new \DateTime());
        }
      }

      try{
        $manager->flush();
        $this->addFlash('success', 'Commande client N° <strong>'.$commande->getReference().'</strong> supprimée avec succès.');
      }  
      catch(\Exception $e){
        $this->addFlash('danger', $e->getMessage());
      }
      if(!empty($request->attributes->get('sells')))
        return $this->redirectToRoute('sells');
      else
        return $this->redirectToRoute('preparing_sells');
    }
  }

    /**
     * @Route("/facture-de-vente/{id}/{settlementId}", name="facture_client", requirements={"id"="\d+", "settlementId"="\d+"})
     * @param CustomerCommande $commande
     * @IsGranted("ROLE_VENTE")
     */
    public function facture_client(int $id, int $settlementId, EntityManagerInterface $manager, CustomerCommande $commande)
    {
      $info = $manager->getRepository(Informations::class)->find(1);
      
      // Sélection des versements 
      $settlement = $manager->getRepository(Settlement::class)->find($settlementId);
      $settlements = $manager->getRepository(Settlement::class)->versementsAnterieurs($id, $settlement);
      // dd($settlements);
      // Configure Dompdf according to your needs
      $pdfOptions = new Options();
      $pdfOptions->set('defaultFont', 'Arial');
      
      // Instantiate Dompdf with our options
      $dompdf = new Dompdf($pdfOptions);
      
      // Retrieve the HTML generated in our twig file
      $html = $this->renderView('Admin/Sell/facture.html.twig', [
          'info'        => $info,
          'commande'    => $commande,
          'settlements' => $settlements
      ]);
      
      // Load HTML to Dompdf
      $dompdf->loadHtml($html);
      //"dompdf/dompdf": "^0.8.3",
      
      // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
      $dompdf->setPaper('A4', 'landscape');
      $dompdf->setPaper('A4', 'portrait');

      // Render the HTML as PDF
      $dompdf->render();

      //File name
      $filename = "vente-".$commande->getReference();

      // Output the generated PDF to Browser (force download)
      $dompdf->stream($filename.".pdf", [
          "Attachment" => false
      ]);
      return $this->render('Admin/Sell/sell-details.html.twig', [
        'current'     => 'sells',
        'commande'    => $commande,
        'settlements' => $settlements
      ]);
    }
}
