<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use App\Entity\Avoir;
use App\Entity\Stock;
use App\Entity\Acompte;
use App\Entity\DetailsAvoir;
use App\Entity\CustomerCommande;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\FonctionsComptabiliteController;
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
    public function retour_de_marchandises(Request $request, EntityManagerInterface $manager, CustomerCommande $commande, int $id, FonctionsComptabiliteController $fonctions)
    {
      $totalReglement = 0;
      foreach ($commande->getSettlements() as $value) {
        $totalReglement = $totalReglement + $value->getAmount();
      }

      if($request->isMethod('post'))
      {
        $exercice  = $fonctions->exercice_en_cours($manager);
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
            $total    = 0;
            $benefice = 0;
            if(count($products) !== 0){
              $avoir = new Avoir();
              $totalAvoir = 0;
              $reference = "AV-".(new \DateTime())->format('Ymd').'-'.(new \DateTime())->format('His');
              $avoir->setDate(new \DateTime());
              $avoir->setMode(3);
              $avoir->setExercice($exercice);
              $avoir->setReference($reference);
              $avoir->setCommande($commande);
              $avoir->setCreatedBy($this->getUser());
              $manager->persist($avoir);

            }
            foreach ($products as $key => $value) {
              $quantity = (int) $value;
              if($quantity != 0){
                $process = true;
                // $product = $manager->getRepository(Product::class)->find($key);
                //On va sélectionner le prix auquel le produit à été vendu
                $storeId = $commande->getStore()->getId();
                foreach ($commande->getProduct() as $item) {
                  if($key === $item->getProduct()->getId()){
                    $product = $item->getProduct();
                    $price = $item->getUnitPrice();
                    $productToReturn = new DetailsAvoir();
                    $productToReturn->setPrice($price);
                    $productToReturn->setAvoir($avoir);
                    $productToReturn->setProduct($product);
                    $productToReturn->setQuantity($quantity);
                    $productToReturn->setCreatedBy($this->getUser());
                    $totalAvoir = $totalAvoir + $price * $quantity;
                    $manager->persist($productToReturn);
                    // Soit $total, la valeur de la marchandise retournée en stock et $benefice, le bénéfice obtenue sur la marchandise retournée
                    $total    = $total + $price * $quantity;
                    $benefice = $benefice + ($item->getBenefice() / $item->getQuantity()) * $quantity;
                    // Lors du retour de marchandises d'un client, il faudra mettre à jour le stock de chacun des produits retournés
                    $stock = $manager->getRepository(Stock::class)->findOneBy(["store" => $storeId, "product" => $product->getId()]);
                    $nouvelleQuantite = $stock->getQuantity() + $quantity;
                    $stock->setQuantity($nouvelleQuantite);
                  }
                }
              }
            }
            //UPDATE customer_commande_details c, product p SET c.benefice = (c.unit_price - p.average_purchase_price) * c.quantity WHERE p.id = c.product_id
            // On va calculer la remise pour la valeur de la marchandise retournée en stock
            $remise            = $total * $commande->getRemise() / $commande->getMontantTtc();
            $montantTva        = $total * $commande->getTva();
            $totalMarchandises = $total - $benefice;
            $resultat          = $benefice - $remise;
            $customer          = $commande->getCustomer();
            $montantAcompte    = $commande->getCustomer()->getAcompte() + $totalReglement;
            $avoir->setMontant($totalReglement);
            $commande->getCustomer()->setAcompte($montantAcompte);

            $acompte = new Acompte();
            $acompte->setCustomer($customer);
            $acompte->setDate(new \DateTime());
            $acompte->setMontant($montantAcompte);
            $acompte->setExercice($exercice);
            $acompte->setCommentaire("Acompte reçu après résiliation de la vente N°$reference");
            $acompte->setCreatedBy($this->getUser());
            $manager->persist($acompte);
            // dd($avoir->getMontant(), $acompte1, $acompte3);

            // dump(["totalHT" => $total, "totalMR" => $totalMarchandise, "RMR" => $remise, "Acompte" => $acompte, "Résul" => $resultat, "bene" => $benefice, "tva" => $montantTva]);
            // die();
            // $fonctions->ecriture_du_retour_de_marchandises_apres_une_vente($manager, $exercice, $totalMarchandise, $montantTva, $resultat, $commande);
            // dd($commande->getCustomer()->getAcompte());
            // On va tester la valeur de $process
            if ($process == false) {
              $this->addFlash('danger', "Impossible de continuer. Aucune donnée envoyée au serveur.");
            } 
            else {
              try{
                $manager->flush();
                $this->addFlash('success', 'Retour de marchandises enregistré <strong>'.$commande->getReference().'</strong> avec succès.');
              } 
              catch(\Exception $e){
                $this->addFlash('danger', $e->getMessage());
              }
            }
          }
          else{
            $this->addFlash('danger', "Le formulaire n'est pas correctement rempli. Veuillez reprendre la procédure.");
          }
          return $this->redirectToRoute('customer_order_details', ["id" => $id]);
        }
      }
      return $this->render('Sell/reset-sell.html.twig', [
        'current'  => 'sells',
        'commande' => $commande,
      ]);
    }
}
