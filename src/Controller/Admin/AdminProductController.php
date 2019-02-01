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
 * @Route("/admin/products")
 */
class AdminProductController extends AbstractController
{
  /**
   * @Route("/", name="product")
   * @IsGranted("ROLE_ADMIN")
   */
   public function index(Request $request, ObjectManager $manager, PaginatorInterface $paginator)
   {
       $search = new ProductSearch();
       $form = $this->createForm(ProductSearchType::class, $search);
       $form->handleRequest($request);

       $products = $paginator->paginate(
         $manager->getRepository(Product::class)->findAllProductsQuery($search),
         $request->query->getInt('page', 1),
         12
       );

       return $this->render('Admin/Product/index.html.twig', [
         'form'     => $form->createView(),
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
        if($form->isSubmitted() && $form->isValid())
        {
            $this->addFlash('success', 'Enregistrement de <strong>'.$product->getCategory()->getName().' '.$product->getMark()->getLabel().' - '.$product->getDescription().'</strong> réussie.');
            $manager->persist($product);
            $manager->flush();
            return $this->redirectToRoute('product');
        }
        return $this->render('Admin/Product/product-add.html.twig', [
          'current' => 'products',
          'form'    => $form->createView()
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
            $this->addFlash('success', 'Mise à jour de <strong>'.$product->getCategory()->getName().' '.$product->getMark()->getLabel().' - '.$product->getDescription().'</strong> réussie.');
            $product->setUpdatedAt(new \DateTime());
            $manager->flush();
            return $this->redirectToRoute('product');
        }
        return $this->render('Admin/Product/product-edit.html.twig', [
          'current' => 'products',
          'product' => $product,
          'form'    => $form->createView()
        ]);
    }
}
