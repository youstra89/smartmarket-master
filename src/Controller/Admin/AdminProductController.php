<?php
// src/Controller/LuckyController.php
namespace App\Controller\Admin;

use App\Entity\Product;
use App\Form\ProductType;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\ProductSearch;
use App\Form\ProductSearchType;

/**
 * @Route("/products")
 */
class AdminProductController extends AbstractController
{
    /**
     * @Route("/", name="product")
     * @IsGranted("ROLE_USER")
     */
    public function index(Request $request, ObjectManager $manager, PaginatorInterface $paginator)
    {
      $search = new ProductSearch();
      $form = $this->createForm(ProductSearchType::class, $search);
      $form->handleRequest($request);

      // $products = $paginator->paginate(
      //   $manager->getRepository(Product::class)->findAllProductsQuery($search),
      //   $request->query->getInt('page', 1),
      //   12
      // );
      $products = $manager->getRepository(Product::class)->findAll();

      return $this->render('Admin/Product/index.html.twig', [
        // 'form'     => $form->createView(),
        'current'  => 'products',
        'products' => $products
      ]);
    }

    /**
     * @Route("/add", name="product.add")
     * @IsGranted("ROLE_ADMIN")
     */
    public function add(Request $request, ObjectManager $manager)
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        $reference = $this->generateReference();
        // dump($reference);
        if($form->isSubmitted() && $form->isValid())
        {
          // $product->setReference($reference);
          $product->setCreatedBy($this->getUser());
          $manager->persist($product);
          try{
            $manager->flush();
            $this->addFlash('success', 'Enregistrement de <strong>'.$product->getCategory()->getName().' '.$product->getMark()->getLabel().' - '.$product->getDescription().'</strong> réussie.');
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          } 
          return $this->redirectToRoute('product');
        }
        return $this->render('Admin/Product/product-add.html.twig', [
          'current' => 'products',
          'form'    => $form->createView(),
          'product_reference' => $reference,
        ]);
    }

    /**
     * @Route("/edit/{id}", name="product.edit")
     * @IsGranted("ROLE_ADMIN")
     * @param Product $product
     */
    public function edit(Request $request, ObjectManager $manager, Product $product)
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
          $product->setUpdatedAt(new \DateTime());
          $product->setUpdatedBy($this->getUser());
          try{
            $manager->flush();
            $this->addFlash('success', 'Mise à jour de <strong>'.$product->getCategory()->getName().' '.$product->getMark()->getLabel().' - '.$product->getDescription().'</strong> réussie.');
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          } 
          return $this->redirectToRoute('product');
        }
        return $this->render('Admin/Product/product-edit.html.twig', [
          'current' => 'products',
          'product' => $product,
          'form'    => $form->createView()
        ]);
    }

    /**
     * @Route("/edit-unit-prices", name="edit.product.unit.price")
     * @IsGranted("ROLE_SUPER_ADMIN")
     */
    public function edit_unit_price(Request $request, ObjectManager $manager)
    {
      $repoProduct = $manager->getRepository(Product::class);
      $products = $repoProduct->findAll();

      if($request->isMethod('post'))
      {
        $data = $request->request->all();
        $token = $data['token'];
        $prices = $data['price'];
        // return new Response(var_dump($data));
        if($this->isCsrfTokenValid('unit_prices', $token)){
          // On va initialiser un compteur qui va compter le nombre de prix modifié
          $cpt = 0;
          foreach ($prices as $key => $value) {
            $product = $repoProduct->find($key);
            if ($value != 0 && $value != $product->getUnitPrice()) {
              $product->setUnitPrice($value);
              $product->setUpdatedAt(new \DateTime());
              $product->setUpdatedBy($this->getUser());
              $cpt++;
            };
          }

          // Si la valeur du compteur est différente de 0, alors il y a eu modification quelque part
          if($cpt != 0)
          {
            try{
              $manager->flush();
              $this->addFlash('success', '<li>Mise à jour des prix unitaires de <strong>'.$cpt.' produits </strong> réussie.');
            } 
            catch(\Exception $e){
              $this->addFlash('danger', $e->getMessage());
            }
          }
          else{
            $this->addFlash('warning', 'Aucun changement observé.');
          }
          return $this->redirectToRoute('product');
        }
      }

      return $this->render('Admin/Product/edit-products-unit-prices.html.twig', [
        'current'  => 'products',
        'products' => $products
      ]);
    }

    // Cette fonction permet de générer les matricules automatiquement
    public function generateReference()
    {
      $manager   = $this->getDoctrine()->getManager();
      $repoProduct = $manager->getRepository(Product::class);
      $product     = $repoProduct->last_saved_product();
      
      if(!empty($product))
      {
        $zero = "";
        $number = (int) substr($product->getReference(), 2);
        $numero_ordre = $number + 1;
        if(strlen($numero_ordre) == 1){
          $zero = '00';
        } 
        elseif (strlen($numero_ordre) == 2) {
          $zero = '0';
        }
        $matricule = "PR".$zero.$numero_ordre;
      }
      else{
        $matricule = "PR001";            
      }
      return $matricule;
    }
}
