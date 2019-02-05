<?php
// src/Controller/LuckyController.php
namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\Commande;
use App\Form\CommandeType;
use App\Entity\Customer;
use App\Form\CustomerType;
use App\Entity\CustomerCommande;
use App\Form\CustomerCommandeType;
use App\Entity\CustomerCommandeDetails;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\CustomerCommandeSearch;
use App\Form\CustomerCommandeSearchType;

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
     * @Route("/add", name="customer.order.add")
     * @IsGranted("ROLE_ADMIN")
     */
    public function add(Request $request, ObjectManager $manager)
    {
        $customerCommande = new CustomerCommande();
        $form = $this->createForm(CustomerCommandeType::class, $customerCommande);
        $form->handleRequest($request);
        // dump($this->getUser());
        if($form->isSubmitted() && $form->isValid())
        {
            $data = $request->request->all();
            if(empty($data['date']))
            {
              $this->addFlash('danger', 'Impossible d\'enregistrer une vente sans la date.');
              return $this->redirectToRoute('customer.order.add');
            }
            else {
              $commande = new Commande();
              $date = new \DateTime($data['date']);
              $commande->setDate($date);
              $manager->persist($commande);
              $seller = $this->getUser();
              $reference = $date->format('Ymd').'.'.(new \DateTime())->format('hm');
              $customerCommande->setReference($reference);
              $customerCommande->setCommande($commande);
              $customerCommande->setSeller($seller);
              $this->addFlash('success', '<li>Enregistrement de la vente du <strong>'.$customerCommande->getCommande()->getDate()->format('d-m-Y').'</strong> réussie.</li><li>Il faut enregistrer les marchandises.</li>');
              $manager->persist($customerCommande);
              $manager->flush();
              return $this->redirectToRoute('customer.order.add.product', ['id' => $customerCommande->getId()]);
            }
        }
        return $this->render('Admin/Sell/sell-add.html.twig', [
          'current' => 'sells',
          'form'    => $form->createView()
        ]);
    }

    /**
     * @Route("/edit/{id}", name="customer.order.edit")
     * @IsGranted("ROLE_ADMIN")
     * @param CustomerCommande $commande
     */
    public function edit(Request $request, ObjectManager $manager, CustomerCommande $commande)
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
        return $this->render('Admin/Sell/sell-edit.html.twig', [
          'current' => 'sells',
          'product' => $product,
          'form'    => $form->createView()
        ]);
    }

    /**
     * @Route("/ajout-de-marchandises-pour-une-vente/{id}", name="customer.order.add.product")
     * @IsGranted("ROLE_ADMIN")
     * @param CustomerCommande $commande
     */
    public function addProduct(Request $request, ObjectManager $manager, CustomerCommande $commande)
    {
        $products = $manager->getRepository(Product::class)->findAll();
        return $this->render('Admin/Sell/sell-add-products.html.twig', [
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
        $ids = $this->get('session')->get('idProductsForSelling');
        if($product->getStock() == 0)
        {
          $this->addFlash('warning', '<strong>'.$product->getCategory()->getName().' '.$product->getMark()->getLabel().' - '.$product->getDescription().'</strong> est fini en stock.');
          return $this->redirectToRoute('customer.order.add.product', ['id' => $commandeId]);
        }
        // On va vérifier la session pour voir si le produit n'est pas déjà sélectionné
        if(!empty($ids)){

          foreach ($ids as $key => $value) {
            if($value === $productId){
              $this->addFlash('warning', '<strong>'.$product->getCategory()->getName().' '.$product->getMark()->getLabel().' - '.$product->getDescription().'</strong> est déjà sélectioné(e).');
              return $this->redirectToRoute('customer.order.add.product', ['id' => $commandeId]);
            }
          }
        }
        // Append value to retrieved array.
        $ids[] = $productId;
        // Set value back to session
        $this->get('session')->set('idProductsForSelling', $ids);

        $this->addFlash('success', '<strong>'.$product->getCategory()->getName().' '.$product->getMark()->getLabel().' - '.$product->getDescription().'</strong> ajouté(e) à la vente.');
        return $this->redirectToRoute('customer.order.add.product', ['id' => $commandeId]);
    }

    /**
     * @Route("/annuler-la-vente-en-cours/{id}", name="customer.commande.reset")
     * @IsGranted("ROLE_ADMIN")
     */
    public function reset_commande(ObjectManager $manager, int $id)
    {
        $this->get('session')->remove('idProductsForSelling');
        $this->addFlash('success', 'Vente a été réinitialisée.');
        return $this->redirectToRoute('customer.order.add.product', ['id' => $id]);
    }

    /**
     * @Route("/enregistrement-d-une-vente/{id}", name="customer.commande.details.save")
     * @IsGranted("ROLE_ADMIN")
     * @param CustomerCommande $commande
     */
    public function save_customer_commande_details(Request $request, ObjectManager $manager, CustomerCommande $commande)
    {
        $ids = $this->get('session')->get('idProductsForSelling');
        $products = $manager->getRepository(Product::class)->findSelectedProducts($ids);
        if($request->isMethod('post'))
        {
          $data = $request->request->all();
          $token = $data['token'];
          // return new Response(var_dump($data));
          if($this->isCsrfTokenValid('vente', $token)){
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
            // return new Response(var_dump([$totalCharge, $totalCommande]));
            // Pour chaque produit de la commande, on doit enregistrer des informations (prix unitaire, qte ...)
            foreach ($price as $priceKey => $priceValue) {
              foreach ($qte as $key => $value) {
                if($priceKey == $key){
                  $prixTotal = 0;
                  $product = $manager->getRepository(Product::class)->find($key);
                  $prixTotal = $value * $priceValue;
                  $stockQte = $product->getStock() - $value;
                  if($value <= 0)
                  {
                    $this->addFlash('danger', 'Quantité de <strong>'.$product->getCategory()->getName().' '.$product->getMark()->getLabel().' - '.$product->getDescription().'</strong> incorrecte.');
                    // return new Response(var_dump("Quantité"));
                    return $this->redirectToRoute('customer.commande.details.save', ['id' => $commande->getId()]);
                  }

                  if($priceValue <= 0)
                  {
                    $this->addFlash('danger', 'Prix de <strong>'.$product->getCategory()->getName().' '.$product->getMark()->getLabel().' - '.$product->getDescription().'</strong> incorrect.');
                    // return new Response(var_dump("Prix"));
                    return $this->redirectToRoute('customer.commande.details.save', ['id' => $commande->getId()]);
                  }

                  if($stockQte < 0)
                  {
                    $this->addFlash('danger', 'Quantité de <strong>'.$product->getCategory()->getName().' '.$product->getMark()->getLabel().' - '.$product->getDescription().'</strong> indisponible en stock.');
                    // return new Response(var_dump("Stock"));
                    return $this->redirectToRoute('customer.commande.details.save', ['id' => $commande->getId()]);
                  }
                  // return new Response(var_dump("Suite"));
                  // On enregistre d'abord les détails de commande
                  $commandeProduit = new CustomerCommandeDetails();
                  $commandeProduit->setCommande($commande);
                  $commandeProduit->setProduct($product);
                  $commandeProduit->setQuantity($value);
                  $commandeProduit->setUnitPrice($priceValue);
                  $commandeProduit->setSubtotal($prixTotal);
                  $totalGeneral += $prixTotal;
                  $manager->persist($commandeProduit);
                  // Ensuite, on met à jour le stock
                  $product->setStock($stockQte);
                  $product->setUpdatedAt(new \DateTime());
                  $t[] = ['prod' => $key, 'qte' => $value, 'price' => $priceValue, 'total' => $prixTotal];
                }
              }
            }
            $commande->getCommande()->setTotalAmount($totalGeneral);
            $commande->getCommande()->setUpdatedAt(new \DateTime());
            $manager->flush();
            $this->get('session')->remove('idProductsForSelling');
            $this->addFlash('success', 'Enregistrement des détails de la vente du <strong>'.$commande->getCommande()->getDate()->format('d-m-Y').'</strong> résussi.');
            return $this->redirectToRoute('sell');
          }
        }

        return $this->render('Admin/Sell/sell-details-save.html.twig', [
            // 'form' => $form->createView(),
            'current'  => 'sells',
            'products' => $products,
            'commande' => $commande
        ]);
    }

    /**
     * @Route("/details-vente/{id}", name="customer.order.details")
     * @IsGranted("ROLE_ADMIN")
     * @param CustomerCommande $commande
     */
     public function customer_order_details(CustomerCommande $commande)
     {
       return $this->render('Admin/Sell/sell-details.html.twig', [
         'current'  => 'sells',
         'commande' => $commande
       ]);
     }
}
