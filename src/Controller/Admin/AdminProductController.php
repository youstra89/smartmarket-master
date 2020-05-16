<?php
// src/Controller/LuckyController.php
namespace App\Controller\Admin;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Family;
use App\Entity\Product;
use App\Entity\Category;
use App\Form\ProductType;
use App\Entity\Informations;
use App\Entity\ProductSearch;
use App\Form\ProductSearchType;
use App\Controller\FonctionsController;
use App\Entity\Mark;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;

// Include Dompdf required namespaces
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
    public function index(Request $request, EntityManagerInterface $manager, PaginatorInterface $paginator)
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
     * @Route("/add", name="product_add")
     * @IsGranted("ROLE_ADMIN")
     */
    public function add(Request $request, EntityManagerInterface $manager, FonctionsController $fonctions)
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
            $this->addFlash('danger', 'Impossible de continuer. Prix d\'achat supérieur au prix de vente.');
            return $this->redirectToRoute('product');
          }
          $mark = !empty($product->getMark()) ? $product->getMark()->getLabel() : '';
          $label = $product->getCategory()->getName().' '.$mark.' '.$product->getDescription();
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
          $product->setAveragePurchasePrice(0);
          $product->setAverageSellingPrice(0);
          $product->setAveragePackageSellingPrice(0);

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
     * @Route("/add-multiple-products", name="add_multiple_products")
     * @IsGranted("ROLE_ADMIN")
     */
    public function add_multiple_products(Request $request, EntityManagerInterface $manager, FonctionsController $fonctions)
    {
        $families     = $manager->getRepository(Family  ::class)->findAll();
        $categories   = $manager->getRepository(Category::class)->findAll();
        $marks        = $manager->getRepository(Mark    ::class)->findAll();
        $last_product = $manager->getRepository(Product ::class)->last_saved_product();
        $manager   = $this->getDoctrine()->getManager();
        // dump($reference);
        if($request->isMethod('post'))
        {
          $data = $request->request->all();
          // return new Response(var_dump($data));
          if(!empty($data['token']))
          {
            $token = $data['token'];
            if($this->isCsrfTokenValid('insert_products', $token)){
              $data              = $request->request->all();
              $familiesP         = $data["families"];
              $categoriesP       = $data["categories"];
              $marksP            = $data["marks"];
              $descriptions      = $data["descriptions"];
              $stocks            = $data["stocks"];
              $purchasing_prices = $data["purchasing_prices"];
              $selling_prices    = $data["selling_prices"];
              $unites            = $data["unites"];

              foreach ($unites as $key => $value) {
                if(
                  !empty($categoriesP[$key]) and 
                  !empty($stocks[$key]) and 
                  !empty($purchasing_prices[$key]) and 
                  !empty($selling_prices[$key])
                ){
                  $product = new Product();
                  $mark     = $this->obtenirLObjetAdequat($marks, (int) $marksP[$key]);
                  $category = $this->obtenirLObjetAdequat($categories, (int) $categoriesP[$key]);
                  $family   = $this->obtenirLObjetAdequat($families, (int) $familiesP[$key]);
                  $product->setMark($mark);
                  $product->setFamily($family);
                  $product->setCategory($category);
                  $product->setPurchasingPrice($purchasing_prices[$key]);
                  $product->setUnitPrice($selling_prices[$key]);
                  $product->setAveragePurchasePrice($purchasing_prices[$key]);
                  $product->setAverageSellingPrice($selling_prices[$key]);
                  $product->setUnite($value);
                  $product->setStock($stocks[$key]);
                  $product->setSecurityStock(0);
                  $product->setDescription($descriptions[$key]);
                  $product->setAveragePackageSellingPrice($selling_prices[$key] * $value);
                  $product->setCreatedBy($this->getUser());
                  $manager->persist($product);
                  $tab[] = $product;
                }
              }

              $nbr = count($tab);
              $reference = $fonctions->generateReference("product", $last_product, $nbr);
              foreach ($reference as $key => $value) {
                $tab[$key]->setReference($reference[$key]);
              }
              // dd($tab);

              try{
                $manager->flush();
                $this->addFlash('success', 'Enregistrement de <strong>'.$nbr.' produits</strong> réussie.');
              } 
              catch(\Exception $e){
                $this->addFlash('danger', $e->getMessage());
              } 
              return $this->redirectToRoute('product');
            }
          }
        }
        return $this->render('Admin/Product/add-multiple-products.html.twig', [
          'current'    => 'products',
          'marks'      => $marks,
          'families'   => $families,
          'categories' => $categories,
        ]);
    }

    public function obtenirLObjetAdequat(array $array, int $id)
    {
      $object = null;
      foreach ($array as $value) {
        if($value->getId() == $id)
          $object = $value;
      }
      return $object;
    }

    /**
     * @Route("/edit/{id}", name="product_edit")
     * @IsGranted("ROLE_ADMIN")
     * @param Product $product
     */
    public function edit(Request $request, EntityManagerInterface $manager, Product $product, int $id)
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
          
          /**
           * Attention !!!!!
           * Lors de la mise à jour d'un produit, il faut bien contrôler les produits qui peuvent être décomposer.
           * Si la valeur de $product->getUnite() change, il faudra que sa nouvelle valeur soit un multiple du stock actuel.
           */
          // $rest = $product->getStock() % $product->getUnite();
          // if($product->getUnite() != 1 and $rest != 0){
          //   $this->addFlash('danger', 'Impossible de continuer. Il faut que la valeur de Unité/produit soit un multiple du stock actuel '.$product->getStock().'.');
          //   return $this->redirectToRoute('product_edit', ["id" => $id]);
          // }
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
            $this->addFlash('success', 'Mise à jour de <strong>'.$product->label().'</strong> réussie.');
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
     * @Route("/define-product-prices/{id}", name="define_product_prices")
     * @IsGranted("ROLE_SUPER_ADMIN")
     * @param Product $product
     */
    public function define_product_prices(Request $request, EntityManagerInterface $manager, Product $product, int $id)
    {
      if($request->isMethod('post'))
      {
        $token = $request->get('_csrf_token');
        $data = $request->request->all();
        $unitPrice                  = (int) $data["unitPrice"];
        $purchasingPrice            = (int) $data["purchasingPrice"];
        $averagePurchasePrice       = (int) $data["averagePurchasePrice"];
        $averageSellingPrice        = (int) $data["averageSellingPrice"];
        $averagePackageSellingPrice = (int) $data["averagePackageSellingPrice"];
        /**
         * Avant l'enregistrement de ces informations, on va faire des vérifications très très importantes.
         */
        // Vérification 1: Le prix de vente doit obligatoirement être supérieur au prix d'achat. On exerce de l'activité 
        //                 pour avoir de l'argent. Pas pour en perdre
        if($purchasingPrice > $unitPrice)
        {
          $this->addFlash('danger', 'Impossible de continuer. Prix d\'achat supérieur au prix de vente.');
          return $this->redirectToRoute('define_product_prices', ["id" => $id]);
        }

        // Vérification 2: Si le produit n'est pas détaillable alors il faut forcément que le prix moyen de vente 
        //                 de l'unité soit égal au prix de vente moyen du carton/paquet
        if($product->getUnite() == 1 and $averageSellingPrice !== $averagePackageSellingPrice)
        {
          $this->addFlash('danger', 'Impossible de continuer. Pour ce produit, les prix moyens de vente de l\'unité et du carton/paquet doivent être égaux.');
          return $this->redirectToRoute('define_product_prices', ["id" => $id]);
        }

        // Vérification 3: Si le produit est détaillable alors le prix de vente moyen du carton pourra être 
        //                 légèrement inférieur au nombre de produit dans le carton multiplié par le prix unitaire de vente
        $prixRaisonable = $averageSellingPrice * $product->getUnite() - $averageSellingPrice;
        if($product->getUnite() !== 1 and $averagePackageSellingPrice < $prixRaisonable)
        {
          $this->addFlash('danger', 'Impossible de continuer. Pour ce produit, le prix moyen de vente du carton devrait être légèrement supérieur à '.number_format($prixRaisonable, 0, ',', ' ').'.');
          return $this->redirectToRoute('define_product_prices', ["id" => $id]);
        }

        
        if($this->isCsrfTokenValid('prices_definition', $token)){
          // On va initier une nouvelle variable pour constater les changement de prix
          $constat = false;
          if($product->getUnitPrice() != $unitPrice or 
             $product->getPurchasingPrice() != $purchasingPrice or 
             $product->getAveragePurchasePrice() != $averagePurchasePrice or 
             $product->getAverageSellingPrice() != $averageSellingPrice or 
             $product->getAveragePackageSellingPrice() != $averagePackageSellingPrice
          ){
            $constat = true;
          }

          if($constat == true){
            $product->setUnitPrice($unitPrice);
            $product->setPurchasingPrice($purchasingPrice);
            $product->setAveragePurchasePrice($averagePurchasePrice);
            $product->setAverageSellingPrice($averageSellingPrice);
            $product->setAveragePackageSellingPrice($averagePackageSellingPrice);
            $product->setUpdatedAt(new \DateTime());
            $product->setUpdatedBy($this->getUser());
            try{
              $manager->flush();
              $this->addFlash('success', 'Mise à jour de <strong>'.$product->label().'</strong> réussie.');
            } 
            catch(\Exception $e){
              $this->addFlash('danger', $e->getMessage());
            } 
          }
          else{
            $this->addFlash('warning', 'Aucune modification constatée pour les prix de <strong>'.$product->label().'</strong>.');
          }

          return $this->redirectToRoute('product');
        }
      }
      return $this->render('Admin/Product/define-product-prices.html.twig', [
        'current' => 'products',
        'product' => $product,
      ]);
    }

    /**
     * @Route("/edit-unit-prices", name="edit.product.unit.price")
     * @IsGranted("ROLE_SUPER_ADMIN")
     */
    public function edit_unit_price(Request $request, EntityManagerInterface $manager)
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
    public function inventaire_de_stock_de_produits(EntityManagerInterface $manager)
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
    public function cout_total_des_produits_en_stock(EntityManagerInterface $manager)
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
    public function impression_cout_total_des_produits_en_stock(EntityManagerInterface $manager)
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

    /**
     * @Route("/impression-de-catalogue-de-produits", name="imprimer_catalogue")
     */
    public function imprimer_catalogue(EntityManagerInterface $manager)
    {
        $idsProducts = $this->get('session')->get('idProductsProviderOrder');
        
        if(empty($idsProducts)){
          $this->addFlash('warning', "Aucun produit enregistré pour le moment.");
          return $this->redirectToRoute('contitution_catalogue');
        }
        $products = $manager->getRepository(Product::class)->findProductsByIds($idsProducts);
        $info = $manager->getRepository(Informations::class)->find(1);

        // $categories = $manager->getRepository(Category::class)->distinctCategories();
        $categories = $this->categorieDesProduitsSelectionnes($products);
        
        // dd($categories);
        // Configure Dompdf according to your needs
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        
        // Instantiate Dompdf with our options
        $dompdf = new Dompdf($pdfOptions);
        
        // Retrieve the HTML generated in our twig file
        $html = $this->renderView('Admin/Product/impression-catalogue-produits.html.twig', [
            'info'       => $info,
            'products'   => $products,
            'categories' => $categories
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
        $dompdf->stream("catalogue.pdf", [
            "Attachment" => false
        ]);
    }

    public function categorieDesProduitsSelectionnes($products)
    {
      $categories = [];
      foreach ($products as $key => $value) {
        $category = $value->getCategory();
        if(!in_array($category, $categories))
          $categories[] = $category;
      }

      return $categories;
    }

    /**
     * @Route("/constitution-de-catalogue-de-produits", name="contitution_catalogue")
     */
    public function constitution_de_catalogue_de_produits(Request $request, EntityManagerInterface $manager)
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
              return $this->redirectToRoute('contitution_catalogue');
            }
            else{
              // $this->addFlash('danger', 'Impossible d\'enregistrer une commande sans la date.');
              $this->get('session')->set('idProductsProviderOrder', $idsProducts);
              return $this->redirectToRoute('imprimer_catalogue');
            }
            return new Response(var_dump($data));
          }
        }
        return $this->render('Admin/Product/constituer-catalogue-produits.html.twig', [
          'current'    => 'products',
          'products'   => $products,
          'categories' => $categories,
        ]);
    }
}
