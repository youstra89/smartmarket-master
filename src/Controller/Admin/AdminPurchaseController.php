<?php
// src/Controller/LuckyController.php
namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\Category;
use App\Entity\Commande;
use App\Form\CommandeType;
use App\Entity\ProviderCommande;
use App\Form\ProviderCommandeType;
use App\Entity\ProviderCommandeDetails;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\ProviderCommandeSearch;
use App\Form\ProviderCommandeSearchType;

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
     * @Route("/add", name="provider.order.add")
     * @IsGranted("ROLE_APPROVISIONNEMENT")
     */
    public function add(Request $request, ObjectManager $manager)
    {
        $providerCommande = new ProviderCommande();
        $form = $this->createForm(ProviderCommandeType::class, $providerCommande);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $data = $request->request->all();
            if(empty($data['date']))
            {
              $this->addFlash('danger', 'Impossible d\'enregistrer une commande sans la date.');
              return $this->redirectToRoute('provider.order.add');
            }
            else {
              $date = new \DateTime($data['date']);
              $reference = $date->format('Ymd').'.'.(new \DateTime())->format('His');
              $totalCharge = $providerCommande->getAdditionalFees() + $providerCommande->getTransport() + $providerCommande->getDedouanement() + $providerCommande->getCurrencyCost() + $providerCommande->getForwardingCost();
              $providerCommande->setReference($reference);
              $providerCommande->setDate($date);
              $providerCommande->setCreatedBy($this->getUser());
              $providerCommande->setTotalFees($totalCharge);
              $manager->persist($providerCommande);
              try{
                $manager->flush();
                $this->addFlash('success', '<li>Enregistrement de la commande du <strong>'.$providerCommande->getDate()->format('d-m-Y').'</strong> réussie.</li><li>Il faut enregistrer les marchandises.</li>');
              } 
              catch(\Exception $e){
                $this->addFlash('danger', $e->getMessage());
              } 
              return $this->redirectToRoute('provider.order.add.product', ['id' => $providerCommande->getId()]);
            }
        }
        return $this->render('Admin/Purchase/purchase-add.html.twig', [
          'current' => 'purchases',
          'form'    => $form->createView()
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
              return $this->redirectToRoute('provider.order.add');
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

                
                // // On enregistre d'abord les détails de commande
                // $commandeProduit = new ProviderCommandeDetails();
                // $commandeProduit->setCommande($providerCommande);
                // $commandeProduit->setProduct($product);
                // $commandeProduit->setQuantity($quantity);
                // $commandeProduit->setUnitPrice($value);
                // $commandeProduit->setSubtotal($subtotal);
                // $commandeGlobalCost += $subtotal;
                // $manager->persist($commandeProduit);
                
                // // Ensuite, on met à jour le stock
                // $product->setStock($stockQte);
                // $product->setUpdatedAt(new \DateTime());
                
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
                $manager->persist($commandeProduit);
                // Ensuite, on met à jour le stock
                $stockQte = $product->getStock() + $quantity;
                $product->setStock($stockQte);
                $product->setUpdatedAt(new \DateTime());
              }
              $providerCommande->setGlobalTotal($totalCommande);
              // return new Response(var_dump($chargeUnitaire));

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
     * @Route("/edit/{id}", name="provider.order.edit")
     * @IsGranted("ROLE_APPROVISIONNEMENT")
     * @param ProviderCommande $commande
     */
    public function edit(Request $request, ObjectManager $manager, ProviderCommande $commande)
    {
        $form = $this->createForm(OrderType::class, $commande);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
          $product->setUpdatedAt(new \DateTime());
          try{
            $manager->flush();
            $this->addFlash('success', 'Mise à jour de la commande du <strong>'.$commande->getDate()->format('d-m-Y').'</strong> réussie.');
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          }
          return $this->redirectToRoute('product');
        }
        return $this->render('Admin/Purchase/purchase-edit.html.twig', [
          'current' => 'purchases',
          'product' => $product,
          'form'    => $form->createView()
        ]);
    }

    /**
     * @Route("/ajout-de-marchandises-pour-une-commande/{id}", name="provider.order.add.product")
     * @IsGranted("ROLE_APPROVISIONNEMENT")
     * @param ProviderCommande $commande
     */
    public function addProduct(Request $request, ObjectManager $manager, ProviderCommande $commande, int $id)
    {
        $products   = $manager->getRepository(Product::class)->allProductsByCategory();
        $categories = $manager->getRepository(Category::class)->distinctCategories();

        if($request->isMethod('post'))
        {
          $data = $request->request->all();
          $token = $data['_csrf_token'];
          if($this->isCsrfTokenValid('provider.order', $token)){
            $idsProducts = $data["products"];
            if(empty($idsProducts))
            {
              $this->addFlash('danger', 'Impossible de continuer. Vous devez obligatoirement sélectionner des produits.');
              return $this->redirectToRoute('provider.order.add.product', ["id" => $id]);
            }
            else{
              // $this->addFlash('danger', 'Impossible d\'enregistrer une commande sans la date.');
              $this->get('session')->set('idProductsProviderOrder', $idsProducts);
              return $this->redirectToRoute('provider.commande.details.save', ["id" => $id]);
            }
            return new Response(var_dump($data));
          }
        }
        return $this->render('Admin/Purchase/purchase-add-products.html.twig', [
          'current'    => 'purchases',
          'products'   => $products,
          'commande'   => $commande,
          'categories' => $categories,
        ]);
    }

    /**
     * @Route("/ajouter-produit-a-la-commande-{id}-{commandeId}", name="add.order.product")
     * @IsGranted("ROLE_APPROVISIONNEMENT")
     * @param Product $product
     */
    public function add_product_command(Product $product, int $commandeId)
    {
        $productId = $product->getId();
        // $commande = $manager->getRepository(Commande::class)->find($commandeId);
        $ids = $this->get('session')->get('idProductsProviderOrder');
        // $stock = $manager->getRepository(Stock::class)->findOneBy(['product' => $productId]);
        // if($stock->getQuantity() == 0)
        // {
        //   $this->addFlash('warning', '<strong>'.$product->getCategory()->getName().' '.$product->getMark()->getLabel().' - '.$product->getDescription().'</strong> est fini en stock.');
        //   return $this->redirectToRoute('provider.order.add.product', ['id' => $commandeId]);
        // }
        // On va vérifier la session pour voir si le produit n'est pas déjà sélectionné
        if(!empty($ids)){

          foreach ($ids as $key => $value) {
            if($value === $productId){
              $this->addFlash('warning', '<strong>'.$product->getCategory()->getName().' '.$product->getMark()->getLabel().' - '.$product->getDescription().'</strong> est déjà ajouté(e).');
              return $this->redirectToRoute('provider.order.add.product', ['id' => $commandeId]);
            }
          }
        }
        // Append value to retrieved array.
        $ids[] = $productId;
        // Set value back to session
        $this->get('session')->set('idProductsProviderOrder', $ids);

        $this->addFlash('success', '<strong>'.$product->getCategory()->getName().' '.$product->getMark()->getLabel().' - '.$product->getDescription().'</strong> ajouté(e) à la commande.');
        return $this->redirectToRoute('provider.order.add.product', ['id' => $commandeId]);
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
}
