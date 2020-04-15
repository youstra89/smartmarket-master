<?php
// src/Controller/LuckyController.php
namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\Depense;
use App\Entity\TypeDepense;
use App\Entity\ReturnedProduct;
use App\Entity\CustomerCommande;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/retour-de-marchandises")
 * @IsGranted("ROLE_ADMIN")
 */
class AdminResetSellController extends AbstractController
{
    /**
     * @Route("/{id}", name="reset_sell", requirements={"id"="\d+"})
     * @param CustomerCommande $commande
     */
    public function retour_de_marchandises(Request $request, ObjectManager $manager, CustomerCommande $commande, int $id)
    {
        if($request->isMethod('post'))
        {
          $data = $request->request->all();
          $token  = $data['token'];
          if($this->isCsrfTokenValid('returned_products', $token)){
            /* 
             * La variable $process va me permettre de savoir si il y a eu au moins un produit qui a été retourné
             * Sa valeur initiale est false. Dès que l'on aura un produit retourné, sa valeur passera à true
             */
            $process = false;
            if(!empty($data['products']))
            {
              $products = $data['products'];
              foreach ($products as $key => $value) {
                $quantity = (int) $value;
                if($quantity != 0)
                {
                  $process = true;
                  $product = $manager->getRepository(Product::class)->find($key);
                  $returnedProduct = new ReturnedProduct();
                  $returnedProduct->setCommande($commande);
                  $returnedProduct->setProduct($product);
                  $returnedProduct->setQuantity($quantity);
                  $returnedProduct->setCreatedAt(new \DateTime());
                  $returnedProduct->setCreatedBy($this->getUser());
                  $manager->persist($returnedProduct);

                  // Lors du retour de marchandises d'un client, il faudra mettre à jour le stock de chacun des produits retournés
                  $nouvelleQuantite = $product->getStock() + $quantity;
                  $product->setStock($nouvelleQuantite);

                  // Par ailleurs, tout retour de marchandises doit être considérer comme une dépense
                  // On va calculer le montant de la dépense
                  foreach ($commande->getProduct() as $prod) {
                    if($prod->getProduct()->getId() == $key){
                      $montantDepense     = $quantity * $prod->getUnitPrice();
                      $typeDepense = $manager->getRepository(TypeDepense::class)->find(1);
                      $descriptionDepense = "Retour de ".$quantity." ".$product->label()." de la commande numéro ".$commande->getReference();
                      $date = new \DateTime();
                      $depense = new Depense();
                      $depense->setType($typeDepense);
                      $depense->setDescription($descriptionDepense);
                      $depense->setAmount($montantDepense);
                      $depense->setDateDepense($date);
                      $depense->setCreatedAt(new \DateTime());
                      $depense->setCreatedBy($this->getUser());
                      $manager->persist($depense);
                    }
                  }
                }
              }

              // On va tester la valeur de $process
              if ($process == false) {
                $this->addFlash('danger', "Impossible de continuer. Aucune donnée envoyée au serveur.");
              } 
              else {
                try{
                  $manager->flush();
                  $this->addFlash('success', 'Retour de marchandises enregistré <strong>'.$commande->getDate()->format('d-m-Y').'</strong> avec succès.');
                } 
                catch(\Exception $e){
                  $this->addFlash('danger', $e->getMessage());
                }
              }
            }
            return $this->redirectToRoute('customer.order.details', ["id" => $id]);
          }
        }
        return $this->render('Admin/Sell/reset-sell.html.twig', [
          'current'  => 'sells',
          'commande' => $commande,
        ]);
    }

    /**
     * @Route("/ajouter-ce-produit-a-la-commande-{id}", name="add.commande.product")
     * @param Product $product
     */
    public function add_product_command(Product $product)
    {
        $productId = $product->getId();
        // $commande = $manager->getRepository(Commande::class)->find($commandeId);
        $ids = $this->get('session')->get('idProductsForSelling');
        if($product->getStock() == 0)
        {
          $this->addFlash('warning', '<strong>'.$product->label().'</strong> est fini en stock.');
          return $this->redirectToRoute('customer.order.add');
        }
        // On va vérifier la session pour voir si le produit n'est pas déjà sélectionné
        if(!empty($ids)){

          foreach ($ids as $key => $value) {
            if($value === $productId){
              $this->addFlash('warning', '<strong>'.$product->label().'</strong> est déjà sélectioné(e).');
              return $this->redirectToRoute('customer.order.add');
            }
          }
        }
        // Append value to retrieved array.
        $ids[] = $productId;
        // Set value back to session
        $this->get('session')->set('idProductsForSelling', $ids);

        $this->addFlash('success', '<strong>'.$product->label().'</strong> ajouté(e) à la vente.');
        return $this->redirectToRoute('customer.order.add');
    }
}
