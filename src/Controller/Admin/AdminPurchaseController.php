<?php
// src/Controller/LuckyController.php
namespace App\Controller\Admin;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Product;
use App\Entity\Informations;
use App\Entity\ProviderCommande;
use App\Entity\ProviderSettlement;
use App\Form\ProviderCommandeType;
use App\Entity\ProviderCommandeSearch;
use App\Entity\ProviderCommandeDetails;
use App\Form\ProviderCommandeSearchType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/commandes")
 */
class AdminPurchaseController extends AbstractController
{
  /**
   * @Route("/", name="purchase")
   * @IsGranted("ROLE_APPROVISIONNEMENT")
   */
   public function index(Request $request, ObjectManager $manager, PaginatorInterface $paginator)
   {
       $search = new ProviderCommandeSearch();
       $form = $this->createForm(ProviderCommandeSearchType::class, $search);
       $form->handleRequest($request);

       $commandes = $paginator->paginate(
         $manager->getRepository(ProviderCommande::class)->commandesFournisseurs($search),
         $request->query->getInt('page', 1),
         20
       );
       return $this->render('Admin/Purchase/index.html.twig', [
         'form'      => $form->createView(),
         'current'   => 'purchases',
         'commandes' => $commandes
       ]);
   }


    /**
     * @Route("/unique-form-provider-order", name="unique_form_provider_order")
     * @IsGranted("ROLE_APPROVISIONNEMENT")
     */
    public function unique_form_provider_order(Request $request, ObjectManager $manager)
    {
        $providerCommande = new ProviderCommande();
        $form = $this->createForm(ProviderCommandeType::class, $providerCommande);
        $products = $manager->getRepository(Product::class)->findAll();
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $data = $request->request->all();
            if(empty($data['date']))
            {
              $this->addFlash('danger', 'Impossible d\'enregistrer une commande sans la date.');
              return $this->redirectToRoute('unique_form_provider_order');
            }
            else {
              $date        = new \DateTime($data['date']);
              $reference   = $date->format('Ymd').'.'.(new \DateTime())->format('His');
              $prices      = $data["prices"];
              $quantities  = $data["quantities"];
              $totalCharge = $providerCommande->getAdditionalFees() + $providerCommande->getTransport() + $providerCommande->getDedouanement() + $providerCommande->getCurrencyCost() + $providerCommande->getForwardingCost();
              $providerCommande->setReference($reference);
              $providerCommande->setDate($date);
              $providerCommande->setCreatedBy($this->getUser());
              $providerCommande->setTotalFees($totalCharge);
              $manager->persist($providerCommande);

              // On va enregistrer les détails de la commande
              // Pour chaque produit de la commande, on doit enregistrer des informations (prix unitaire, qte ...)
              $totalCommande = 0;
              foreach ($data['sousTotal'] as $key => $value) {
                $totalCommande += $value;
              }

              $commandeGlobalCost = 0;
              foreach ($prices as $key => $value) {
                $product  = $manager->getRepository(Product::class)->find($key);
                $quantity = $quantities[$key];
                $subtotal = $value * $quantity;

                if($quantity <= 0)
                {
                  $this->addFlash('danger', 'Quantité de <strong>'.$product->getLabel().'</strong> incorrecte.');
                  // return new Response(var_dump("Quantité"));
                  return $this->redirectToRoute('unique_form_provider_order');
                }
                
                if($value <= 0)
                {
                  $this->addFlash('danger', 'Prix d\'achat de <strong>'.$product->getLabel().'</strong> incorrect.');
                  // return new Response(var_dump("Prix"));
                  return $this->redirectToRoute('unique_form_provider_order');
                }
                
                $part = 0;
                $product = $manager->getRepository(Product::class)->find($key);
                // On va commencer par calculer le prix de revient de chaque marchandise
                /* Pour se faire, on déternime d'abord le pourcentage du prix total de chaque
                  * article (marchandise, ou encore produit) dans le prix total de la commande
                  **/
                $part = ($subtotal * 100) / $totalCommande;
                // On cherche maintenant le coût total des charges pour l'article actuel
                // (l'article dans la boucle foreach
                $chargeProduit = ($totalCharge * $part) / 100;
                // On divise maintenant cette somme ($chargeProduit) par le nombre de
                // produit de la commande.
                $chargeUnitaire = $chargeProduit / $quantity;

                // On enregistre d'abord les détails de commande
                $commandeProduit = new ProviderCommandeDetails();
                $commandeProduit->setCommande($providerCommande);
                $commandeProduit->setProduct($product);
                $commandeProduit->setQuantity($quantity);
                $commandeProduit->setUnitPrice($value);
                $commandeProduit->setSubtotal($subtotal);
                $commandeProduit->setMinimumSellingPrice($value + $chargeUnitaire);
                $commandeGlobalCost += $subtotal;
                $manager->persist($commandeProduit);
                // Ensuite, on met à jour le stock
                $stockQte = $product->getStock() + $quantity;
                $product->setStock($stockQte);
                $product->setUpdatedAt(new \DateTime());
              }
              $providerCommande->setTotalAmount($commandeGlobalCost);
              $providerCommande->setGlobalTotal($commandeGlobalCost + $totalCharge);
              // dd($providerCommande);

              try{
                $manager->flush();
                $this->addFlash('success', '<li>Enregistrement de la commande du <strong>'.$providerCommande->getDate()->format('d-m-Y').'</strong> réussie.</li><li>Il faut enregistrer les marchandises.</li>');
              } 
              catch(\Exception $e){
                $this->addFlash('danger', $e->getMessage());
              } 
              return $this->redirectToRoute('purchase.selling.price', ['id' => $providerCommande->getId()]);
            }
        }
        return $this->render('Admin/Purchase/purchase-add-unique-form.html.twig', [
          'current'  => 'purchases',
          'products' => $products,
          'form'     => $form->createView()
        ]);
    }


    /**
     * @Route("/annuler-la-commande-en-cours/{id}", name="provider.commande.reset")
     * @IsGranted("ROLE_APPROVISIONNEMENT")
     */
    public function reset_commande(ObjectManager $manager, int $id)
    {
        $this->get('session')->remove('idProductsProviderOrder');
        $this->addFlash('success', 'La commande a été réinitialisée.');
        return $this->redirectToRoute('provider.order.add.product', ['id' => $id]);
    }

    /**
     * @Route("/enregistrement-d-une-commande/{id}", name="provider.commande.details.save")
     * @IsGranted("ROLE_APPROVISIONNEMENT")
     * @param ProviderCommande $commande
     */
    public function save_provider_commande_details(Request $request, ObjectManager $manager, ProviderCommande $commande)
    {
        $ids = $this->get('session')->get('idProductsProviderOrder');
        $products = $manager->getRepository(Product::class)->findSelectedProducts($ids);
        if($request->isMethod('post'))
        {
          $data = $request->request->all();
          $token = $data['token'];
          // return new Response(var_dump($data));
          if($this->isCsrfTokenValid('achat', $token)){
            // Soit les variables
            //  - $qte les différentes quantités des différents produits et
            //  - $price les différents prix unitaires
            //  - $totalCharge, le coût total de toutes les charges
            $qte = $data['quantityH'];
            $price = $data['priceH'];
            $totalGeneral = 0;
            $totalCommande = 0;
            foreach ($data['sousTotal'] as $key => $value) {
              $totalCommande += $value;
            }
            $totalCharge = $commande->getAdditionalFees() + $commande->getTransport() + $commande->getDedouanement() + $commande->getCurrencyCost() + $commande->getForwardingCost();
            $t = [];
            // return new Response(var_dump([$totalCharge, $totalCommande]));
            // Pour chaque produit de la commande, on doit enregistrer des informations (prix unitaire, qte ...)
            foreach ($price as $priceKey => $priceValue) {
              foreach ($qte as $key => $value) {
                if($priceKey == $key){
                  $prixTotal = 0;
                  $part = 0;
                  $product = $manager->getRepository(Product::class)->find($key);
                  $prixTotal = $value * $priceValue;
                  // On va commencer par calculer le prix de revient de chaque marchandise
                  /* Pour se faire, on déternime d'abord le pourcentage du prix total de chaque
                   * article (marchandise, ou encore produit) dans le prix total de la commande
                   **/
                  $part = ($prixTotal * 100) / $totalCommande;
                  // On cherche maintenant le coût total des charges pour l'article actuel
                  // (l'article dans la boucle foreach
                  $chargeProduit = ($totalCharge * $part) / 100;
                  // On divise maintenant cette somme ($chargeProduit) par le nombre de
                  // produit de la commande.
                  $chargeUnitaire = $chargeProduit / $value;

                  // On enregistre d'abord les détails de commande
                  $commandeProduit = new ProviderCommandeDetails();
                  $commandeProduit->setCommande($commande);
                  $commandeProduit->setProduct($product);
                  $commandeProduit->setQuantity($value);
                  $commandeProduit->setUnitPrice($priceValue);
                  $commandeProduit->setSubtotal($prixTotal);
                  $commandeProduit->setMinimumSellingPrice($priceValue + $chargeUnitaire);
                  $totalGeneral += $prixTotal;
                  $manager->persist($commandeProduit);
                  // Ensuite, on met à jour le stock
                  $stockQte = $product->getStock() + $value;
                  $product->setStock($stockQte);
                  $product->setUpdatedAt(new \DateTime());
                  $t[] = ['prod' => $key, 'qte' => $value, 'price' => $priceValue, 'total' => $prixTotal];
                }
              }
            }
            // return new Response(var_dump($t));
            $coutGlobal = $commande->getTotalFees() + $totalGeneral;
            $commande->setGlobalTotal($coutGlobal);
            $commande->setTotalAmount($totalGeneral);
            $commande->setUpdatedAt(new \DateTime());
            $commande->setUpdatedBy($this->getUser());

            $this->get('session')->remove('idProductsProviderOrder');
            try{
              $manager->flush();
              $this->addFlash('success', 'Enregistrement des détails de la commande fournisseur du <strong>'.$commande->getDate()->format('d-m-Y').'</strong> résussi.');
            } 
            catch(\Exception $e){
              $this->addFlash('danger', $e->getMessage());
            }
            return $this->redirectToRoute('purchase.selling.price', ['id' => $commande->getId()]);
          }
        }

        return $this->render('Admin/Purchase/purchase-details-save.html.twig', [
            'current'  => 'purchases',
            'products' => $products,
            'commande' => $commande
        ]);
    }


    /**
     * @Route("/prix-de-revient-des-marchandises-d-une-commande/{id}", name="purchase.selling.price")
     * @IsGranted("ROLE_APPROVISIONNEMENT")
     * @param ProviderCommande $commande
     */
    public function prix_de_revient(Request $request, ObjectManager $manager, ProviderCommande $commande)
    {
        // $products = $commande->getProviderCommandeDetails;
        if($request->isMethod('post'))
        {
          $data = $request->request->all();
          $token = $data['token'];
          if($this->isCsrfTokenValid('prix_de_vente', $token)){
            $price = $data['price'];
            // return new Response(var_dump($price));
            foreach ($price as $key => $value) {
              if ($value == 0) {
                $this->addFlash('danger', 'Echec, la marge bénéficiaire ne doit pas être nulle.');
                return $this->redirectToRoute('purchase.selling.price', ['id' => $commande->getId()]);
              }
              else {
                $product = $manager->getRepository(ProviderCommandeDetails::class)->find($key);
                $fixedPrice = $product->getMinimumSellingPrice() + $value;
                $product->setFixedAmount($fixedPrice);
              }
            }
            try{
              $manager->flush();
              $this->addFlash('success', 'Enregistrement des prix de vente de la commande fournisseur du <strong>'.$commande->getDate()->format('d-m-Y').'</strong> résussi.');
            } 
            catch(\Exception $e){
              $this->addFlash('danger', $e->getMessage());
            }
            return $this->redirectToRoute('purchase');
          }
        }

        return $this->render('Admin/Purchase/purchase-selling-price.html.twig', [
            'current'  => 'purchases',
            'commande' => $commande
        ]);
    }

    /**
     * @Route("/details-de-commande-fournisseur/{id}", name="provider.order.details")
     * @IsGranted("ROLE_APPROVISIONNEMENT")
     * @param ProviderCommande $commande
     */
    public function provider_order_details(ProviderCommande $commande)
    {
      return $this->render('Admin/Purchase/purchase-details.html.twig', [
        'current'  => 'purchases',
        'commande' => $commande
      ]);
    }


    /**
     * @Route("/reglement-de-vente/{id}", name="provider_settlement", requirements={"id"="\d+"})
     * @IsGranted("ROLE_APPROVISIONNEMENT")
     * @param ProvidererCommande $commande
     */
    public function provider_settlements(Request $request, int $id, ProviderCommande $commande, ObjectManager $manager)
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
        if($this->isCsrfTokenValid('token_reglement_fournisseur', $token)){
          if(empty($date)){
            $this->addFlash('danger', 'Saisir une valuer pour la date.');
            return $this->redirectToRoute('provider_settlement', ['id' => $commandeId]);
          }
          if(empty($amount) or $amount < 0){
            $this->addFlash('danger', 'Montant incorrect. Saisir une valeur supérieure à 0.');
            // return new Response("Montant nul ou négatif");
            return $this->redirectToRoute('provider_settlement', ['id' => $commandeId]);
          }
          $newTotal = $amount + $total;
          $reglementMemeDate = $manager->getRepository(ProviderSettlement::class)->reglementMemeDate($id, $date->format('Y-m-d'));
          $dernierVersement = $manager->getRepository(ProviderSettlement::class)->lastSettlement($id);
          if(!empty($dernierVersement) and $dernierVersement->getDate() > $date)
          {
            $this->addFlash('danger', 'Impossible d\'enregistrer ce versement car la date est antérieure au dernier versement ('. $dernierVersement->getDate()->format('d-m-Y') .').');
            return $this->redirectToRoute('provider_settlement', ['id' => $commandeId]);
          }
          elseif(!empty($reglementMemeDate))
          {
            $this->addFlash('danger', 'Impossible d\'enregistrer un deuxième versement pour la même date ('. $date->format('d-m-Y') .'). Vous pouvez cependant modifier le montant du premier versement.');
            return $this->redirectToRoute('provider_settlement', ['id' => $commandeId]);
          }
          elseif($newTotal > $commande->getTotalAmount())
          {
            $this->addFlash('danger', 'Montant incorrect. La somme des règlements est supérieure au montant total da la commande.');
            return $this->redirectToRoute('provider_settlement', ['id' => $commandeId]);
          }
          elseif($newTotal < $commande->getTotalAmount())
          {
            $this->addFlash('success', 'Règlement fournisseur bien enregistré. Cependant la commande n\'est pas soldée.');
          }
          elseif ($newTotal == $commande->getTotalAmount()) {
            $this->addFlash('success', 'Règlement fournisseur enregistré avec succès. Commande soldée.');
            $commande->setEnded(true);
          }
          $settlementNumber = $this->generateSettlementNumber(empty($dernierVersement) ? null : $dernierVersement);
          $settlement = new ProviderSettlement();
          $settlement->setDate($date);
          $settlement->setAmount($amount);
          $settlement->setNumber($settlementNumber);
          $settlement->setCreatedBy($this->getUser());
          $settlement->setCommande($commande);
          $manager->persist($settlement);
          try{
            $manager->flush();
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          }
          if(!empty($request->attributes->get('option')))
            return $this->redirectToRoute('purchase');
          else
            return $this->redirectToRoute('accounting_creance');
        }
      }
      return $this->render('Admin/Purchase/settlement.html.twig', [
        'current'  => 'sells',
        'reste'    => $reste,
        'commande' => $commande,
      ]);
    }

    /**
     * @Route("/bon-de-commande/{id}", name="bon_commande", requirements={"id"="\d+"})
     * @param ProviderCommande $commande
     * @IsGranted("ROLE_APPROVISIONNEMENT")
     */
    public function bon_de_commande(ObjectManager $manager, ProviderCommande $commande, int $id)
    {
        $info = $manager->getRepository(Informations::class)->find(1);
        
        // dd($commande);
        $settlements = $manager->getRepository(ProviderSettlement::class)->findByCommande($id);

        // Configure Dompdf according to your needs
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        
        // Instantiate Dompdf with our options
        $dompdf = new Dompdf($pdfOptions);
        
        // Retrieve the HTML generated in our twig file
        $html = $this->renderView('Admin/Purchase/bon-commande.html.twig', [
            'info'        => $info,
            'commande'    => $commande,
            'settlements' => $settlements,
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
          'current'     => 'sells',
          'commande'    => $commande,
          'settlements' => $settlements
        ]);
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
}
