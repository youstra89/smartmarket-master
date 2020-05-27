<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use App\Entity\Stock;
use App\Entity\Store;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/admin/depots")
 * @IsGranted("ROLE_ADMIN")
 */
class AdminStoreController extends AbstractController
{
    /**
     * @Route("/", name="stores")
     */
    public function index(EntityManagerInterface $manager)
    {
        $stores = $manager->getRepository(Store::class)->findAll();
        return $this->render('Store/index.html.twig', [
          'current' => 'stores',
          'stores'   => $stores
        ]);
    }

    /**
     * @Route("/add", name="store_add")
     */
    public function add(Request $request, EntityManagerInterface $manager)
    {
      
      if($request->isMethod('post')){
        $data = $request->request->all();
        $token = $data['token'];
        if($this->isCsrfTokenValid('enregistrement_de_depot', $token)){
          $name         = $data['name'];
          $number       = $data['number'];
          $comment      = $data['comment'];
          $localisation = $data['localisation'];
          // dd($data);
          $store = new Store();
          $store->setName($name);
          $store->setPhoneNumber($number);
          $store->setComment($comment);
          $store->setLocalisation($localisation);
          $store->setCreatedAt(new \DateTime());
          $store->setCreatedBy($this->getUser());
          $manager->persist($store);

          // Après l'enregistrement d'un dépôt, on va initialiser le stock de chaque produit
          $products = $manager->getRepository(Product::class)->findAll();
          foreach ($products as $value) {
            if($value->getIsDeleted() == 0){
              $stock = new Stock();
              $stock->setProduct($value);
              $stock->setStore($store);
              $stock->setQuantity(0);
              $manager->persist($stock);
            }
          }

          try{
            $manager->flush();
            $this->addFlash('success', 'Enregistrement du dépôt <strong>'.$store->getName().'</strong> réussie.');
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          } 

          return $this->redirectToRoute('stores');
        }
        else{
          $this->addFlash('danger', "Formulaire invalide. Veuillez reprendre la procédure. Il semble que le formulaire ait été laissé pendant longtemps actif.");
          return $this->redirectToRoute('store_add');
        }
      }

          
      return $this->render('Store/store-add.html.twig', [
        'current' => 'stores',
      ]);
    }
          
    /**
     * @Route("/edit/{id}", name="store_edit")
     * @param Store $store
     */
    public function edit(Request $request, EntityManagerInterface $manager, Store $store, int $id)
    {
      if($store->getIsRoot() == 1){
        $this->addFlash('danger', "Ce dépôt ne peut être éditer.");
        return $this->redirectToRoute('stores');
      }
      if($request->isMethod('post')){
        $data = $request->request->all();
        // dd($data);
        $token = $data['token'];
        if($this->isCsrfTokenValid('mise_a_jour_de_depot', $token)){
          $name         = $data['name'];
          $number       = $data['number'];
          $comment      = $data['comment'];
          $localisation = $data['localisation'];
          $change = false;
          if ($name != $store->getName()) {
            $change = true;
            $store->setName($name);
          }
          if ($number != $store->getPhoneNumber()) {
            $change = true;
            $store->setPhoneNumber($number);
          }
          if ($comment != $store->getComment()) {
            $change = true;
            $store->setComment($comment);
          }
          if ($localisation != $store->getLocalisation()) {
            $change = true;
            $store->setLocalisation($localisation);
          }

          if ($change == true) {
            $store->setUpdatedAt(new \DateTime());
            $store->setUpdatedBy($this->getUser());
            try{
              $manager->flush();
              $this->addFlash('success', 'Mise à jour du dépôt <strong>'.$store->getName().'</strong> réussie.');
            } 
            catch(\Exception $e){
              $this->addFlash('danger', $e->getMessage());
            } 
          }
          else{
            $this->addFlash('warning', 'Aucun changement observé dans la mise à jour du dépôt <strong>'.$store->getName().'</strong>.');
          }

          return $this->redirectToRoute('stores');
        }
        else{
          $this->addFlash('danger', "Formulaire invalide. Veuillez reprendre la procédure. Il semble que le formulaire ait été laissé pendant longtemps actif.");
          return $this->redirectToRoute('store_edit', ["id" => $id]);
        }
      }

      return $this->render('Store/store-edit.html.twig', [
        'current'  => 'stores',
        'store' => $store
      ]);
    }


    /**
     * @Route("/stock-de-produit-du-depot/{id}", name="store_info")
     * @param Store $store
     */
    public function store_info(Request $request, EntityManagerInterface $manager, Store $store, int $id)
    {
      return $this->render('Store/stock.html.twig', [
        'current'  => 'stores',
        'store' => $store
      ]);
    }
}
