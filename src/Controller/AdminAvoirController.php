<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Product;
use App\Entity\Customer;
use App\Entity\Settlement;
use App\Entity\ComptaCompte;
use App\Entity\Informations;
use App\Entity\ComptaExercice;
use App\Entity\CustomerCommande;
use App\Entity\ComptaCompteExercice;
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
use App\Entity\Avoir;
use NumberFormatter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/les-factures-avoir")
 */
class AdminAvoirController extends AbstractController
{
    /**
     * @Route("/", name="avoir")
     * @IsGranted("ROLE_VENTE")
     */
    public function index(Request $request, EntityManagerInterface $manager)
    {
      $exercice  = $manager->getRepository(ComptaExercice::class)->dernierExerciceEnCours();
      $exerciceId = $exercice->getId();
      $avoirs = $manager->getRepository(Avoir::class)->avoirDeLExercice($exerciceId);

      return $this->render('Avoir/index.html.twig', [
        'current' => 'avoir',
        'avoirs'   => $avoirs
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
            $remise     = isset($data["remise"]) ? $data["remise"] : 0;
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
              $tva         = $commande->getTva();
              $ancienTotal = $commande->getMontantTtc();
              $montantTtc  = $total + $total * ($tva/100);
              $commande->setTotalAmount($total);
              $commande->setMontantTtc($montantTtc);
              $commande->setNetAPayer($montantTtc - $remise);
              $commande->setUpdatedAt(new \DateTime());
              $commande->setUpdatedBy($this->getUser());
              try{
                $fonctions->ecritureDeModificationDeVente($manager, $commande, $ancienTotal);
                $manager->flush();
                $this->addFlash('success', 'La commande N°<strong>'.$commande->getReference().'</strong> du <strong>'.$commande->getDate()->format('d-m-Y').'</strong> à été modifiée avec succès.');
              } 
              catch(\Exception $e){
                $this->addFlash('danger', $e->getMessage());
                return $this->redirectToRoute('edit_sell', ["id" => $id]);
              }
            }
            else{
              $this->addFlash('warning', 'Aucun changement constaté.');
            }
            return $this->redirectToRoute('sell');
          }
          else{
            $this->addFlash('danger', 'Jeton de sécurité non valide.');
          }
        }
      }
      return $this->render('Sell/sell-edit.html.twig', [
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
                $beneficeSurProduit = ($value - $product->getAveragePurchasePrice()) * $quantity;
                $commandeProduit->setBenefice($beneficeSurProduit);
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
      
      return $this->render('Sell/prepare-sell-for-customer.html.twig', [
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

      return $this->render('Sell/deliver-customer-commande.html.twig', [
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
      // // $total = array_sum(array_map('getValue', $reglements));
      $reglements = $commande->getSettlements();
      $total = 0;
      foreach ($reglements as $key => $value) {
        $total += $value->getAmount();
      }
      // dump($total);
      $reste = $commande->getNetAPayer() - $total;
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
            $this->addFlash('danger', 'Saisir une valeur pour la date.');
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
            if($amount != $commande->getNetAPayer()) {
              $this->addFlash('danger', 'Montant incorrect. La valeur saisie n\'est pas égale au montant total da la commande.');
              return $this->redirectToRoute('settlement', ['id' => $id]);
            }
            else {
              $this->addFlash('success', 'Règlement enregistré avec succès. Commande soldée.');
              $commande->setEnded(true);
            }
          }

          $result = $this->generate_new_settlement($manager, $commande, $amount, $mode, $date);
          if($result[0] == 500){
            $this->addFlash('danger', $result[1]);
            return $this->redirectToRoute('unique_form_for_selling');
          }
          $settlement = $result[2];
          
          try{
            $fonctions->ecriture_de_reglements_clients_dans_le_journal_comptable($manager, $mode, $amount, $exercice, $date, $settlement);
            $manager->flush();
            if ($soldee == true)
              $this->addFlash('success', 'Règlement enregistré avec succès. Commande soldée.');
            else
              $this->addFlash('success', 'Règlement bien enregistré. Cependant la commande n\'est pas soldée.');
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          }
          return $this->redirectToRoute('customer.order.details', ['id' => $id]);
          // if(empty($data['from']))
          // else
          //   return $this->redirectToRoute('accounting_creance');
        }
        else{
          $this->addFlash('danger', 'Jéton de sécurité invalide. Vous avez certement mis trop de temps sur cette page.');
          // return $this->redirectToRoute('unique_form_for_selling');
        }
      }
      return $this->render('Sell/settlement.html.twig', [
        'current'  => 'sells',
        'reste'    => $reste,
        'commande' => $commande,
      ]);
    }


    // On va écrire une fonction qui permet de générer un règlement
    public function generate_new_settlement(EntityManagerInterface $manager, CustomerCommande $commande, int $amount, int $mode, \DateTime $date, int $id = 0)
    {
      $reglements = $commande->getSettlements();
      // $total = array_sum(array_map('getValue', $reglements));
      $total = 0;
      foreach ($reglements as $key => $value) {
        $total += $value->getAmount();
      }
      $newTotal = $amount + $total;
      $dernierVersement = $manager->getRepository(Settlement::class)->lastSettlement($id);
      /**
       * On va faire une certaine vérification. Le but est de savoir si le compte qui est sélectionné pour le règlement a
       * un solde débiteur et s'il peut satisfaire le règlement.
       * 
       * On selectionne donc ce compte qui est soit la banque, soit la caisse. Si le solde du compte en question est supérieur 
       * ou égal au montant du règlement, alors on peut continuer le règlement. Sinon, on renvoie une erreur à l'utilisateur
       */
      $status = 200;
      $message = "Enregistrement de règlement";
      if($mode == 3 and $commande->getCustomer()->getAcompte() < $amount){
        $montantDuCompte = $commande->getCustomer()->getAcompte();
        $compte = "Client - Acomptes et avances versées";
        $status = 500;
        $message = 'Le solde du compte <strong>'.$compte.' ('.number_format($montantDuCompte, 0, ',', ' ').' F</strong>) est inféreur au montant saisie qui est de <strong>'.number_format($amount, 0, ',', ' ').' F</strong>';
      }
      elseif($newTotal > $commande->getNetAPayer()){
        $status = 500;
        $message = 'Montant incorrect. La somme des règlements est supérieure au montant total da la commande.';
      }
      elseif ($newTotal == $commande->getNetAPayer()) {
        $commande->setEnded(true);
      }
      
      
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
      return [$status, $message, $settlement];
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
          elseif($newTotal > $commande->getNetAPayer())
          {
            $this->addFlash('danger', 'Montant incorrect. La somme des règlements est supérieure au montant total da la commande.');
            return $this->redirectToRoute('customer.order.details', ['id' => $commandeId]);
          }
          elseif($newTotal < $commande->getNetAPayer())
          {
            $commande->setEnded(false);
          }
          elseif ($newTotal == $commande->getNetAPayer()) {
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
      return $this->render('Sell/edit-settlement.html.twig', [
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
      // $ftm = new NumberFormatter('fr', NumberFormatter::SPELLOUT);
      // $nbr = $ftm->format(1522530);
      // dd($nbr);
      return $this->render('Sell/sell-details.html.twig', [
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

      return $this->render('Sell/commande-details.html.twig', [
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
      $settlement  = $manager->getRepository(Settlement::class)->find($settlementId);
      $settlements = $manager->getRepository(Settlement::class)->versementsAnterieurs($id, $settlement);
      // dd($settlements);
      // Configure Dompdf according to your needs
      $pdfOptions = new Options();
      $pdfOptions->set('defaultFont', 'Arial');
      
      // Instantiate Dompdf with our options
      $dompdf = new Dompdf($pdfOptions);
      
      // Retrieve the HTML generated in our twig file
      $html = $this->renderView('Sell/facture.html.twig', [
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
      return $this->render('Sell/sell-details.html.twig', [
        'current'     => 'sells',
        'commande'    => $commande,
        'settlements' => $settlements
      ]);
    }

    /**
     * @Route("/impression-de-ticket-de-caisse/{id}/{settlementId}", name="ticket_de_ciasse", requirements={"id"="\d+"})
     * @param CustomerCommande $commande
     * @IsGranted("ROLE_VENTE")
     */
    public function ticket_de_ciasse(int $id, EntityManagerInterface $manager, int $settlementId, CustomerCommande $commande)
    {
      $info = $manager->getRepository(Informations::class)->find(1);
      $settlement  = $manager->getRepository(Settlement::class)->find($settlementId);
      $settlements = $manager->getRepository(Settlement::class)->versementsAnterieurs($id, $settlement);
      // Configure Dompdf according to your needs
      $pdfOptions = new Options();
      $pdfOptions->set('defaultFont', 'Arial');
      
      // Instantiate Dompdf with our options
      $dompdf = new Dompdf($pdfOptions);
      
      // Retrieve the HTML generated in our twig file
      $html = $this->renderView('Sell/ticket-de-caisse.html.twig', [
          'info'        => $info,
          'commande'    => $commande,
          'settlements' => $settlements,
      ]);
      
      // Load HTML to Dompdf
      $dompdf->loadHtml($html);
      // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
      // $dompdf->setPaper('A8', 'portrait');
      $dompdf->setPaper('A8', 'landscape');

      $orientation = "landscape";
      $nbr = count($commande->getProduct());
      $nbr = $nbr * 25 + 350;
      $paper = [0, 0, $nbr, 240];
      // dd($paper);
      $dompdf->setPaper($paper, $orientation);

      // Render the HTML as PDF
      $dompdf->render();

      //File name
      $filename = "vente-".$commande->getReference();

      // Output the generated PDF to Browser (force download)
      $dompdf->stream($filename.".pdf", [
          "Attachment" => false
      ]);
      return $this->render('Sell/sell-details.html.twig', [
        'current'     => 'sells',
        'commande'    => $commande,
      ]);
    }
}
