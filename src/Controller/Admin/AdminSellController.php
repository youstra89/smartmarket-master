<?php
// src/Controller/LuckyController.php
namespace App\Controller\Admin;

use App\Entity\Product;
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
 * @Route("/admin/ventes")
 */
class AdminSellController extends AbstractController
{
  /**
   * @Route("/", name="sell")
   * @IsGranted("ROLE_ADMIN")
   */
   public function index(Request $request, ObjectManager $manager, PaginatorInterface $paginator)
   {
       $search = new ProviderCommandeSearch();
       $form = $this->createForm(ProviderCommandeSearchType::class, $search);
       $form->handleRequest($request);

       $commandes = $paginator->paginate(
         $manager->getRepository(ProviderCommande::class)->findAllVisibleQuery($search),
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
     * @Route("/add", name="customer.order.add")
     * @IsGranted("ROLE_ADMIN")
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
              return $this->redirectToRoute('customer.order.add');
            }
            else {
              $commande = new Commande();
              $date = new \DateTime($data['date']);
              $commande->setDate($date);
              $manager->persist($commande);
              $reference = $date->format('Ymd').'.'.(new \DateTime())->format('hm');
              $totalCharge = $providerCommande->getAdditionalFees() + $providerCommande->getTransport() + $providerCommande->getDedouanement() + $providerCommande->getCurrencyCost() + $providerCommande->getForwardingCost();
              $providerCommande->setReference($reference);
              $providerCommande->setCommande($commande);
              $providerCommande->setTotalFees($totalCharge);
              $this->addFlash('success', '<li>Enregistrement de l\'achat du <strong>'.$providerCommande->getCommande()->getDate()->format('d-m-Y').'</strong> réussie.</li><li>Il faut enregistrer les marchandises.</li>');
              $manager->persist($providerCommande);
              $manager->flush();
              return $this->redirectToRoute('customer.order.add.product', ['id' => $commande->getId()]);
            }
        }
        return $this->render('Admin/Sell/purchase-add.html.twig', [
          'current' => 'sells',
          'form'    => $form->createView()
        ]);
    }

    /**
     * @Route("/edit/{id}", name="customer.order.edit")
     * @IsGranted("ROLE_ADMIN")
     * @param ProviderCommande $commande
     */
    public function edit(Request $request, ObjectManager $manager, ProviderCommande $commande)
    {
        $form = $this->createForm(OrderType::class, $commande);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $this->addFlash('success', 'Mise à jour de la commande du <strong>'.$commande->getCommande()->getDate()->format('d-m-Y').'</strong> réussie.');
            $product->setUpdatedAt(new \DateTime());
            $manager->flush();
            return $this->redirectToRoute('product');
        }
        return $this->render('Admin/Sell/purchase-edit.html.twig', [
          'current' => 'sells',
          'product' => $product,
          'form'    => $form->createView()
        ]);
    }

    /**
     * @Route("/ajout-de-marchandises-pour-un-achat/{id}", name="customer.order.add.product")
     * @IsGranted("ROLE_ADMIN")
     * @param ProviderCommande $commande
     */
    public function addProduct(Request $request, ObjectManager $manager, ProviderCommande $commande)
    {
        $products = $manager->getRepository(Product::class)->findAll();
        return $this->render('Admin/Sell/purchase-add-products.html.twig', [
          'current'  => 'sells',
          'products' => $products,
          'commande' => $commande,
        ]);
    }

    /**
     * @Route("/ajouter-produit-a-la-commande-{id}-{commandeId}", name="add.order.product")
     * @IsGranted("ROLE_ADMIN")
     * @param Product $product
     */
    public function add_product_command(Request $request, ObjectManager $manager, Product $product, int $commandeId)
    {
        $productId = $product->getId();
        // $commande = $manager->getRepository(Commande::class)->find($commandeId);
        $ids = $this->get('session')->get('idProductsProviderOrder');
        // $stock = $manager->getRepository(Stock::class)->findOneBy(['product' => $productId]);
        // if($stock->getQuantity() == 0)
        // {
        //   $this->addFlash('warning', '<strong>'.$product->getCategory()->getName().' '.$product->getMark()->getLabel().' - '.$product->getDescription().'</strong> est fini en stock.');
        //   return $this->redirectToRoute('customer.order.add.product', ['id' => $commandeId]);
        // }
        // On va vérifier la session pour voir si le produit n'est pas déjà sélectionné
        if(!empty($ids)){

          foreach ($ids as $key => $value) {
            if($value === $productId){
              $this->addFlash('warning', '<strong>'.$product->getCategory()->getName().' '.$product->getMark()->getLabel().' - '.$product->getDescription().'</strong> est déjà ajouté(e).');
              return $this->redirectToRoute('customer.order.add.product', ['id' => $commandeId]);
            }
          }
        }
        // Append value to retrieved array.
        $ids[] = $productId;
        // Set value back to session
        $this->get('session')->set('idProductsProviderOrder', $ids);

        $this->addFlash('success', '<strong>'.$product->getCategory()->getName().' '.$product->getMark()->getLabel().' - '.$product->getDescription().'</strong> ajouté(e) à la commande.');
        return $this->redirectToRoute('customer.order.add.product', ['id' => $commandeId]);
    }

    /**
     * @Route("/annuler-la-commande-en-cours/{id}", name="provider.commande.reset")
     * @IsGranted("ROLE_ADMIN")
     */
    public function reset_commande(ObjectManager $manager, int $id)
    {
        $this->get('session')->remove('idProductsProviderOrder');
        $this->addFlash('success', 'La commande a été réinitialisée.');
        return $this->redirectToRoute('customer.order.add.product', ['id' => $id]);
    }

    /**
     * @Route("/enregistrement-d-une-commande/{id}", name="provider.commande.details.save")
     * @IsGranted("ROLE_ADMIN")
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
            $commande->getCommande()->setTotalAmount($totalGeneral);
            $commande->getCommande()->setUpdatedAt(new \DateTime());
            $manager->flush();
            $this->get('session')->remove('idProductsProviderOrder');
            $this->addFlash('success', 'Enregistrement des détails de la commande fournisseur du <strong>'.$commande->getCommande()->getDate()->format('d-m-Y').'</strong> résussi.');
            return $this->redirectToRoute('purchase.selling.price', ['id' => $commande->getId()]);
          }
        }

        return $this->render('Admin/Sell/purchase-details-save.html.twig', [
            // 'form' => $form->createView(),
            'current'  => 'sells',
            'products' => $products,
            'commande' => $commande
        ]);
    }


    /**
     * @Route("/prix-de-revient-des-marchandises-d-un-achat/{id}", name="purchase.selling.price")
     * @IsGranted("ROLE_ADMIN")
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
            $manager->flush();
            $this->addFlash('success', 'Enregistrement des prix de vente de la commande fournisseur du <strong>'.$commande->getCommande()->getDate()->format('d-m-Y').'</strong> résussi.');
            return $this->redirectToRoute('purchase');
          }
        }

        return $this->render('Admin/Sell/purchase-selling-price.html.twig', [
            'current'  => 'sells',
            'commande' => $commande
        ]);
    }

    /**
     * @Route("/details-commande-fournisseur/{id}", name="customer.order.details")
     * @IsGranted("ROLE_ADMIN")
     * @param ProviderCommande $commande
     */
     public function provider_order_details(ProviderCommande $commande)
     {
       return $this->render('Admin/Sell/purchase-details.html.twig', [
         'current'  => 'sells',
         'commande' => $commande
       ]);
     }
}
