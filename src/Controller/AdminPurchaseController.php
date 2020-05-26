<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Product;
use App\Entity\Informations;
use App\Entity\ComptaExercice;
use App\Entity\ProviderCommande;
use App\Entity\ProviderSettlement;
use App\Form\ProviderCommandeType;
use App\Entity\ComptaCompteExercice;
use App\Entity\ProviderCommandeSearch;
use App\Entity\ProviderCommandeDetails;
use App\Form\ProviderCommandeSearchType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\FonctionsComptabiliteController;
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
   public function index(Request $request, EntityManagerInterface $manager, PaginatorInterface $paginator)
   {
       $search = new ProviderCommandeSearch();
       $form = $this->createForm(ProviderCommandeSearchType::class, $search);
       $form->handleRequest($request);

       $commandes = $paginator->paginate(
         $manager->getRepository(ProviderCommande::class)->commandesFournisseurs($search),
         $request->query->getInt('page', 1),
         20
       );
       return $this->render('Purchase/index.html.twig', [
         'form'      => $form->createView(),
         'current'   => 'purchases',
         'commandes' => $commandes
       ]);
   }


    /**
     * @Route("/unique-form-provider-order", name="unique_form_provider_order")
     * @IsGranted("ROLE_APPROVISIONNEMENT")
     */
    public function unique_form_provider_order(Request $request, EntityManagerInterface $manager, FonctionsComptabiliteController $fonctions)
    {
        $providerCommande = new ProviderCommande();
        $form = $this->createForm(ProviderCommandeType::class, $providerCommande);
        $products = $manager->getRepository(Product::class)->findAll();
        $exercice  = $manager->getRepository(ComptaExercice::class)->dernierExerciceEnCours();
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $data = $request->request->all();
            $token = $data['token'];
            if($this->isCsrfTokenValid('achat', $token)){
              if(empty($data['date']))
              {
                $this->addFlash('danger', 'Impossible d\'enregistrer une commande sans la date.');
                return $this->redirectToRoute('unique_form_provider_order');
              }
  
              $date        = new \DateTime($data['date']);
              $reference   = $date->format('Ymd').'.'.(new \DateTime())->format('His');
              $prices      = $data["prices"];
              $tva         = (int) $data["tva"];
              $remise      = (int) $data["remise"];
              $quantities  = $data["quantities"];
              // dd($prices);
              $totalCharge = $providerCommande->getAdditionalFees() + $providerCommande->getTransport() + $providerCommande->getDedouanement() + $providerCommande->getCurrencyCost() + $providerCommande->getForwardingCost();
              $providerCommande->setReference($reference);
              $providerCommande->setExercice($exercice);
              $providerCommande->setDate($date);
              $providerCommande->setRemise($remise);
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
                $value = $value / $product->getUnite();
                $quantity = $quantities[$key] * $product->getUnite();
                $subtotal = $value * $quantity;
  
                if($quantity <= 0)
                {
                  $this->addFlash('danger', 'Quantité de <strong>'.$product->label().'</strong> incorrecte.');
                  // return new Response(var_dump("Quantité"));
                  return $this->redirectToRoute('unique_form_provider_order');
                }
                
                if($value <= 0)
                {
                  $this->addFlash('danger', 'Prix d\'achat de <strong>'.$product->label().'</strong> incorrect.');
                  // return new Response(var_dump("Prix"));
                  return $this->redirectToRoute('unique_form_provider_order');
                }
                
                $part = 0;
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
                // dd($commandeProduit);
                $manager->persist($commandeProduit);
                $stockQte = $product->getStock() + $quantity;
                /**
                 * On va aussi déterminer le prix d'achat moyen. En effet, un même produit peut se retrouver en stock avec différent prix d'achat.
                 * Il faut alors qu'on détermine le prix moyen d'achat. Cela nous permettra de savoir lors d'une vente quelle est la marge bénéficiaire.
                 * 
                 * 1 - Pour calculer ce prix moyenne, on va multiplier le nombre de produits en stock par le prix moyen d'achat courant.
                 * 2 - Puis on multiplie le nombre de produits nouvellement achéter par le prix d'achat lors l'achat en cours d'enregistrement
                 * 3 - Et enfin, on fait la somme de ces deux produits qu'on va diviser par le nombre total de produits.
                 */
                $produit1  = $product->getStock() * $product->getAveragePurchasePrice();
                $produit2  = $quantity * $value;
                $prixMoyen = ($produit1 + $produit2) / $stockQte;
                $prixMoyen = (int) round($prixMoyen);
                // Ensuite, on met à jour le stock
                $product->setStock($stockQte);
                $product->setAveragePurchasePrice($prixMoyen);
                $product->setUpdatedAt(new \DateTime());
              }
              // dd($product);
              $providerCommande->setTotalAmount($commandeGlobalCost);
              $providerCommande->setGlobalTotal($commandeGlobalCost + $totalCharge);
              $providerCommande->setTva($tva);
              $providerCommande->setMontantTtc($commandeGlobalCost + $commandeGlobalCost * ($tva/100));
              $netAPayer = $providerCommande->getMontantTtc() - $remise;
              $providerCommande->setNetAPayer($netAPayer);
              
              $transport       = $providerCommande->getTransport();
              $dedouanement    = $providerCommande->getDedouanement();
              $currency_cost   = $providerCommande->getCurrencyCost();
              $forwarding_cost = $providerCommande->getForwardingCost();
              $additional_fees = $providerCommande->getAdditionalFees();
  
              try{
                $fonctions->ecritureDAchatDansLeJournalComptable($manager, $netAPayer, $tva, $exercice, $date, $providerCommande);
                $fonctions->ecritureDesChargesDUnAchatDansLeJournalComptable($manager, $transport, $dedouanement, $currency_cost, $forwarding_cost, $additional_fees, $providerCommande, $exercice);
                $manager->flush();
                $this->addFlash('success', '<li>Enregistrement de la commande du <strong>'.$providerCommande->getDate()->format('d-m-Y').'</strong> réussie.</li><li>Il faut enregistrer les marchandises.</li>');
              } 
              catch(\Exception $e){
                $this->addFlash('danger', $e->getMessage());
              } 
              return $this->redirectToRoute('purchase.selling.price', ['id' => $providerCommande->getId()]);
            }
            else{
              $this->addFlash('danger', 'Jéton de sécurité invalide. Vous avez certement mis trop de temps sur cette page.');
              return $this->redirectToRoute('unique_form_provider_order');
            }
        }
        return $this->render('Purchase/purchase-add-unique-form.html.twig', [
          'current'  => 'purchases',
          'products' => $products,
          'form'     => $form->createView()
        ]);
    }


    /**
     * @Route("/annuler-la-commande-en-cours/{id}", name="provider.commande.reset")
     * @IsGranted("ROLE_APPROVISIONNEMENT")
     */
    public function reset_commande(EntityManagerInterface $manager, int $id)
    {
        $this->get('session')->remove('idProductsProviderOrder');
        $this->addFlash('success', 'La commande a été réinitialisée.');
        return $this->redirectToRoute('provider.order.add.product', ['id' => $id]);
    }


    /**
     * @Route("/prix-de-revient-des-marchandises-d-une-commande/{id}", name="purchase.selling.price")
     * @IsGranted("ROLE_APPROVISIONNEMENT")
     * @param ProviderCommande $commande
     */
    public function prix_de_revient(Request $request, EntityManagerInterface $manager, ProviderCommande $commande, int $id)
    {
        // $products = $commande->getProviderCommandeDetails;
        if($request->isMethod('post'))
        {
          $data = $request->request->all();
          $token = $data['token'];
          if($this->isCsrfTokenValid('prix_de_vente', $token)){
            $prices = $data['price'];
            // return new Response(var_dump($price));
            foreach ($prices as $key => $value) {
              if ($value == 0) {
                $this->addFlash('danger', 'Echec, la marge bénéficiaire ne doit pas être nulle.');
                return $this->redirectToRoute('purchase.selling.price', ['id' => $id]);
              }
              else {
                $commandeItem = $manager->getRepository(ProviderCommandeDetails::class)->find($key);
                $fixedPrice   = $commandeItem->getMinimumSellingPrice() + $value;
                $commandeItem->setFixedAmount($fixedPrice);

                // On va aussi déterminer le prix de vente minimum de tout les stock
                $product   = $commandeItem->getProduct();
                $quantity  = $commandeItem->getQuantity();
                $stockAvantCommande = $product->getStock() - $quantity;

                $produit1  = $stockAvantCommande * $product->getAverageSellingPrice();
                $produit2  = $quantity * $fixedPrice;
                $prixMoyen = ($produit1 + $produit2) / $product->getStock();
                $prixMoyen = (int) round($prixMoyen);
                $product->setAverageSellingPrice($prixMoyen);
                $product->setAveragePackageSellingPrice($prixMoyen * $product->getUnite());
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

        return $this->render('Purchase/purchase-selling-price.html.twig', [
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
      return $this->render('Purchase/purchase-details.html.twig', [
        'current'  => 'purchases',
        'commande' => $commande
      ]);
    }


    /**
     * @Route("/reglement-de-l-achat/{id}", name="provider_settlement", requirements={"id"="\d+"})
     * @IsGranted("ROLE_APPROVISIONNEMENT")
     * @param ProvidererCommande $commande
     */
    public function provider_settlements(Request $request, int $id, ProviderCommande $commande, EntityManagerInterface $manager, FonctionsComptabiliteController $fonctions)
    {
      if ($commande->getEnded() == true) {
        $this->addFlash('warning', 'Cette commande est déjà soldée.');
        return $this->redirectToRoute('purchase');
      }
      $exercice  = $manager->getRepository(ComptaExercice::class)->dernierExerciceEnCours();
      $exerciceId = $exercice->getId();

      $reglements = $commande->getSettlements();
      // $total = array_sum(array_map('getValue', $reglements));
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
        $mode   = $data['mode'];
        $amount = $data['amount'];
        // return new Response(var_dump($data));
        if($this->isCsrfTokenValid('token_reglement_fournisseur', $token)){
          if(empty($date)){
            $this->addFlash('danger', 'Saisir une valeur pour la date.');
            return $this->redirectToRoute('provider_settlement', ['id' => $id]);
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
            // return new Response("Montant nul ou négatif");
            return $this->redirectToRoute('provider_settlement', ['id' => $id]);
          }

          /**
           * On va faire une certaine vérification. Le but est de savoir si le compte qui est sélectionné pour le règlement a
           * un solde débiteur et s'il peut satisfaire le règlement.
           * 
           * On selectionne donc ce compte qui est soit la banque, soit la caisse. Si le solde du compte en question est supérieur 
           * ou égal au montant du règlement, alors on peut continuer le règlement. Sinon, on renvoie une erreur à l'utilisateur
           */
          if($mode == 1){
            $compteAcrediter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(13, $exerciceId);
            $montantDuCompte = $compteAcrediter->getMontantFinal();
            $compte = "Caisse";
          }
          elseif($mode == 2){
            $compteAcrediter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(12, $exerciceId);
            $montantDuCompte = $compteAcrediter->getMontantFinal();
            $compte = "Banque";
          }
          elseif($mode == 3){
            $compteAcrediter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(28, $exerciceId);
            $montantDuCompte = $commande->getProvider()->getAcompte();
            $compte = "Fournisseur - Acomptes et avances versées";
          }
          
          if($montantDuCompte < $amount)
          {
            $this->addFlash('danger', 'Le solde du compte <strong>'.$compte.' ('.number_format($montantDuCompte, 0, ',', ' ').' F</strong>) est inféreur au montant saisie qui est de <strong>'.number_format($amount, 0, ',', ' ').' F</strong>');
            return $this->redirectToRoute('provider_settlement', ['id' => $id]);
          }
          $newTotal = $amount + $total;
          $reglementMemeDate = $manager->getRepository(ProviderSettlement::class)->reglementMemeDate($id, $date->format('Y-m-d'));
          $dernierVersement  = $manager->getRepository(ProviderSettlement::class)->lastSettlement($id);
          if(!empty($dernierVersement) and $dernierVersement->getDate() > $date)
          {
            $this->addFlash('danger', 'Impossible d\'enregistrer ce versement car la date est antérieure au dernier versement ('. $dernierVersement->getDate()->format('d-m-Y') .').');
            return $this->redirectToRoute('provider_settlement', ['id' => $id]);
          }
          elseif(!empty($reglementMemeDate))
          {
            $this->addFlash('danger', 'Impossible d\'enregistrer un deuxième versement pour la même date ('. $date->format('d-m-Y') .'). Vous pouvez cependant modifier le montant du premier versement.');
            return $this->redirectToRoute('provider_settlement', ['id' => $id]);
          }
          elseif($newTotal > $commande->getNetAPayer())
          {
            $this->addFlash('danger', 'Montant incorrect. La somme des règlements est supérieure au montant total da la commande.');
            return $this->redirectToRoute('provider_settlement', ['id' => $id]);
          }
          elseif($newTotal < $commande->getNetAPayer())
          {
            $this->addFlash('success', 'Règlement fournisseur bien enregistré. Cependant la commande n\'est pas soldée.');
          }
          elseif ($newTotal == $commande->getNetAPayer()) {
            $this->addFlash('success', 'Règlement fournisseur enregistré avec succès. Commande soldée.');
            $commande->setEnded(true);
          }
          $settlementNumber = $this->generateSettlementNumber(empty($dernierVersement) ? null : $dernierVersement);
          $settlement = new ProviderSettlement();
          $settlement->setDate($date);
          $settlement->setAmount($amount);
          $settlement->setModePaiement($mode);
          $settlement->setNumber($settlementNumber);
          $settlement->setCreatedBy($this->getUser());
          $settlement->setCommande($commande);
          $manager->persist($settlement);
          try{
            $fonctions->ecritureDeReglementsFournisseursDansLeJournalComptable($manager, $mode, $amount, $exercice, $date, $settlement);
            $manager->flush();
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          }
          if(!empty($request->attributes->get('option')))
            return $this->redirectToRoute('purchase');
          else
            return $this->redirectToRoute('accounting_arriere');
        }
        else{
          $this->addFlash('danger', 'Jéton de sécurité invalide. Vous avez certement mis trop de temps sur cette page.');
        }
      }
      return $this->render('Purchase/settlement.html.twig', [
        'current'  => 'purchases',
        'reste'    => $reste,
        'commande' => $commande,
      ]);
    }

    /**
     * @Route("/bon-de-commande/{id}", name="bon_commande", requirements={"id"="\d+"})
     * @param ProviderCommande $commande
     * @IsGranted("ROLE_APPROVISIONNEMENT")
     */
    public function bon_de_commande(EntityManagerInterface $manager, ProviderCommande $commande, int $id)
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
        $html = $this->renderView('Purchase/bon-commande.html.twig', [
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
