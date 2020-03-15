<?php
// src/Controller/LuckyController.php
namespace App\Controller\Admin;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Product;
use App\Form\ProductType;
use App\Entity\ProductSearch;
use App\Form\ProductSearchType;
use App\Controller\FonctionsController;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Security\Http\Util\TargetPathTrait;

// Include Dompdf required namespaces
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

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
    public function add(Request $request, ObjectManager $manager, FonctionsController $fonctions)
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        $manager   = $this->getDoctrine()->getManager();
        $last_product  = $manager->getRepository(Product::class)->last_saved_product();
        $reference = $fonctions->generateReference("product", $last_product);
        // dump($reference);
        if($form->isSubmitted() && $form->isValid())
        {
          // $product->setReference($reference);
          if($product->getPurchasingPrice() > $product->getUnitPrice())
          {
            $this->addFlash('error', 'Impossible de continuer. Prix d\'achat supérieur au prix de vente.');
            return $this->redirectToRoute('product');
          }
          $mark = !empty($product->getMark()) ? $product->getMark()->getLabel() : '';
          $label = $product->getCategory()->getName().' '.$mark.' - '.$product->getDescription();
          $product->setCreatedBy($this->getUser());
          
          /** @var UploadedFile $imageFile */
          $imageFile = $form->get('image')->getData();

          // this condition is needed because the 'brochure' field is not required
          // so the PDF file must be processed only when a file is uploaded
          if ($imageFile) {
              $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
              // this is needed to safely include the file name as part of the URL
              $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
              $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

              // Move the file to the directory where brochures are stored
              try {
                  $imageFile->move(
                      $this->getParameter('images_directory'),
                      $newFilename
                  );
              } catch (FileException $e) {
                  // ... handle exception if something happens during file upload
              }

              // updates the 'brochureFilename' property to store the PDF file name
              // instead of its contents
              $product->setImage($newFilename);
          }

          $manager->persist($product);
          try{
            $manager->flush();
            $this->addFlash('success', 'Enregistrement de <strong>'.$label.'</strong> réussie.');
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          } 
          return $this->redirectToRoute('product');
        }
        return $this->render('Admin/Product/product-add.html.twig', [
          'current' => 'products',
          'form'    => $form->createView(),
          'reference' => $reference,
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
          if($product->getPurchasingPrice() > $product->getUnitPrice())
          {
            $this->addFlash('danger', 'Impossible de continuer. Prix d\'achat supérieur au prix de vente.');
            return $this->redirectToRoute('product');
          }
          $mark = !empty($product->getMark()) ? $product->getMark()->getLabel() : '';
          $label = $product->getCategory()->getName().' '.$mark.' - '.$product->getDescription();
          /** @var UploadedFile $imageFile */
          $imageFile = $form->get('image')->getData();

          // this condition is needed because the 'brochure' field is not required
          // so the PDF file must be processed only when a file is uploaded
          if ($imageFile) {
              $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
              // this is needed to safely include the file name as part of the URL
              $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
              $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

              // Move the file to the directory where brochures are stored
              try {
                  $imageFile->move(
                      $this->getParameter('images_directory'),
                      $newFilename
                  );
              } catch (FileException $e) {
                  // ... handle exception if something happens during file upload
              }

              // updates the 'brochureFilename' property to store the PDF file name
              // instead of its contents
              $product->setImage($newFilename);
          }
          $product->setUpdatedAt(new \DateTime());
          $product->setUpdatedBy($this->getUser());
          try{
            $manager->flush();
            $this->addFlash('success', 'Mise à jour de <strong>'.$label.'</strong> réussie.');
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
        return new Response(var_dump($data));
        $token = $data['token'];
        $purchasingPrice = $data['purchasingPrice'];
        $sellingPrice = $data['sellingPrice'];
        // return new Response(var_dump($data));
        if($this->isCsrfTokenValid('unit_prices', $token)){
          // On va initialiser un compteur qui va compter le nombre de prix modifié
          $cpt = 0;
          foreach ($sellingPrice as $key => $value) {
            $product = $repoProduct->find($key);
            if ($value != 0 && $value != $product->getUnitPrice()) {
              $product->setUnitPrice($value);
              $product->setUpdatedAt(new \DateTime());
              $product->setUpdatedBy($this->getUser());
              $cpt++;
            };

            // Pour les prix d'achat également
            $productPurchasingPrice = $purchasingPrice[$key];
            if ($productPurchasingPrice != 0 && $productPurchasingPrice != $product->getPurchasingPrice()) {
              $product->setPurchasingPrice($productPurchasingPrice);
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

    /**
     * @Route("/impression-de-inventaire-de-stock-de-produits", name="impression_inventaire")
     */
    public function inventaire_de_stock_de_produits(ObjectManager $manager)
    {
        $products = $manager->getRepository(Product::class)->findAll();
        if(empty($products)){
            $this->addFlash('warning', "Aucun produit enregistré pour le moment.");
            return $this->redirectToRoute('product');
        }

        // Configure Dompdf according to your needs
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        
        // Instantiate Dompdf with our options
        $dompdf = new Dompdf($pdfOptions);
        
        // Retrieve the HTML generated in our twig file
        $html = $this->renderView('Admin/Product/inventaire-stock-de-produits.html.twig', [
            'products'  => $products
        ]);
        
        // Load HTML to Dompdf
        $dompdf->loadHtml($html);
        //"dompdf/dompdf": "^0.8.3",
        
        // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->setPaper('A4', 'landscape');

        // Render the HTML as PDF
        $dompdf->render();

        // Output the generated PDF to Browser (force download)
        $dompdf->stream("stock-".(new \DateTime())->format('d-m-Y H:i:s').".pdf", [
            "Attachment" => false
        ]);
    }

    /**
     * @Route("/cout-total-des-produits-en-stock", name="cout_total_en_stock")
     */
    public function cout_total_des_produits_en_stock(ObjectManager $manager)
    {
        $products = $manager->getRepository(Product::class)->findAll();
        if(empty($products)){
            $this->addFlash('warning', "Aucun produit enregistré pour le moment.");
            return $this->redirectToRoute('product');
        }

        return $this->render('Admin/Product/cout-total-produits.html.twig', [
          'current'  => 'products',
          'products' => $products
        ]);
    }

    /**
     * @Route("/impression-du-cout-total-des-produits-en-stock", name="impression_cout_total_en_stock")
     */
    public function impression_cout_total_des_produits_en_stock(ObjectManager $manager)
    {
        $products = $manager->getRepository(Product::class)->findAll();
        if(empty($products)){
            $this->addFlash('warning', "Aucun produit enregistré pour le moment.");
            return $this->redirectToRoute('product');
        }

        // Configure Dompdf according to your needs
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        
        // Instantiate Dompdf with our options
        $dompdf = new Dompdf($pdfOptions);
        
        // Retrieve the HTML generated in our twig file
        $html = $this->renderView('Admin/Product/impression-cout-total-produits.html.twig', [
            'products'  => $products
        ]);
        
        // Load HTML to Dompdf
        $dompdf->loadHtml($html);
        //"dompdf/dompdf": "^0.8.3",
        
        // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->setPaper('A4', 'landscape');

        // Render the HTML as PDF
        $dompdf->render();

        // Output the generated PDF to Browser (force download)
        $dompdf->stream("stock-".(new \DateTime())->format('d-m-Y H:i:s').".pdf", [
            "Attachment" => false
        ]);
    }
}
