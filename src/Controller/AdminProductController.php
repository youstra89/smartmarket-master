<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Mark;
use App\Entity\Stock;
use App\Entity\Store;
use App\Entity\Family;
use App\Entity\Product;
use App\Entity\Activite;
use App\Entity\Category;
use App\Form\ProductType;
use App\Entity\Informations;
use App\Entity\ProductSearch;
use App\Entity\ComptaExercice;
use App\Form\ProductSearchType;

// Include Dompdf required namespaces
use App\Controller\FonctionsController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\Cache\ItemInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Controller\FonctionsComptabiliteController;
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
    public function index(Request $request, EntityManagerInterface $manager, Stopwatch $stopwatch, CacheInterface $cache)
    {
      $stopwatch->start("produits");
      // $cache->get("affiche-produit", function(ItemInterface $item){
      //   $item->expiresAfter(10);
      //   return sleep(3);
      // });
      $stores = $manager->getRepository(Store::class)->findAll();
      $products = $manager->getRepository(Product::class)->findAll();
      $stopwatch->stop("produits");

      return $this->render('Product/index.html.twig', [
        'current'  => 'products',
        'products' => $products,
        'stores'   => $stores
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
        $manager      = $this->getDoctrine()->getManager();
        $last_product = $manager->getRepository(Product::class)->last_saved_product();
        $reference    = $fonctions->generateReference("product", $last_product);
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

          $data = $request->request->all();
          $codebarre = isset($data['codebarre']) ? $data['codebarre'] : null;
          if(null != $codebarre){
            $exitingProduct = $manager->getRepository(Product::class)->findOneBy(["code_barre" => $codebarre]);
            if(empty($exitingProduct)){
              $product->setCodeBarre($codebarre);
            }
            elseif(!empty($exitingProduct)){
              $this->addFlash('danger', "Code barre <strong>$codebarre</strong> déjà engistré pour le produit <strong>".$exitingProduct->label()."</strong>");
              return $this->redirectToRoute('product');
            }
          }
          $product->setAveragePurchasePrice($product->getPurchasingPrice());
          $product->setAverageSellingPrice($product->getUnitPrice());
          $product->setAveragePackageSellingPrice($product->getUnitPrice() * $product->getUnite());
          $manager->persist($product);

          // Après l'enregistrement d'un dépôt, on va initialiser le stock de chaque produit
          $stores = $manager->getRepository(Store::class)->findAll();
          foreach ($stores as $value) {
            if($value->getIsDeleted() == 0){
              $stock = new Stock();
              $stock->setProduct($product);
              $stock->setStore($value);
              $stock->setQuantity(0);
              $value->getIsRoot() == 1 ? $stock->setIsRoot(1) : $stock->setIsRoot(0);
              $manager->persist($stock);
            }
          }

          try{
            $manager->flush();
            $this->addFlash('success', 'Enregistrement du produit <strong>'.$label.'</strong> réussie.');
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          } 
          return $this->redirectToRoute('product');
        }
        return $this->render('Product/product-add.html.twig', [
          'current' => 'products',
          'form'    => $form->createView(),
          'reference' => $reference,
        ]);
    }

    /**
     * @Route("/add-multiple-products", name="add_multiple_products")
     * @IsGranted("ROLE_ADMIN")
     */
    public function add_multiple_products(Request $request, EntityManagerInterface $manager, FonctionsController $fonctions, FonctionsComptabiliteController $fonctionsComptables)
    {
      // return $this->redirectToRoute('product');

      $families     = $manager->getRepository(Family  ::class)->findAll();
      $categories   = $manager->getRepository(Category::class)->findAll();
      $marks        = $manager->getRepository(Mark    ::class)->findAll();
      $last_product = $manager->getRepository(Product ::class)->last_saved_product();
      $exercice     = $fonctionsComptables->exercice_en_cours($manager);
      // dump($reference);
      if($request->isMethod('post'))
      {
        $data = $request->request->all();
        // return new Response(var_dump($data));
        if(!empty($data['token']))
        {
          $token = $data['token'];
          if($this->isCsrfTokenValid('insert_products', $token)){
            $stores            = $manager->getRepository(Store::class)->findAll();
            $montant           = 0;
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
                $product->setSecurityStock(0);
                $product->setDescription($descriptions[$key]);
                $product->setAveragePackageSellingPrice($selling_prices[$key] * $value);
                $product->setCreatedBy($this->getUser());
                $montant = $montant + $stocks[$key] * $value * $purchasing_prices[$key];
                $manager->persist($product);
                foreach ($stores as $item) {
                  if($item->getIsDeleted() == 0){
                    $newStock = new Stock();
                    $newStock->setProduct($product);
                    $newStock->setStore($item);
                    $newStock->setQuantity(0);
                    $item->getIsRoot() == 1 ? $newStock->setIsRoot(1) : $newStock->setIsRoot(0);
                    $item->getIsRoot() == 1 ? $newStock->setQuantity($stocks[$key]) : $newStock->setQuantity(0);
                    $manager->persist($newStock);
                  }
                }
                $tab[] = $product;
              }
            }

            $nbr = count($tab);
            $reference = $fonctions->generateReference("product", $last_product, $nbr);
            if($nbr == 1)
              $tab[0]->setReference($reference);
            else{
              foreach ($reference as $key => $value) {
                $tab[$key]->setReference($reference[$key]);
              }
            }
            // dd($tab);
            try{
              $fonctionsComptables->ecriture_de_l_enregistrement_du_stock_initial_dans_le_journal_comptable($manager, $montant, $exercice);
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
      return $this->render('Product/add-multiple-products.html.twig', [
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
          $data = $request->request->all();
          $codebarre = isset($data['codebarre']) ? $data['codebarre'] : null;
          if(null != $codebarre){
            $exitingProduct = $manager->getRepository(Product::class)->findOneBy(["code_barre" => $codebarre]);
            if(empty($exitingProduct)){
              $product->setCodeBarre($codebarre);
            }
            elseif(!empty($exitingProduct) and $exitingProduct->getId() != $id){
              $this->addFlash('danger', "Code barre <strong>$codebarre</strong> déjà engistré pour le produit <strong>".$exitingProduct->label()."</strong>");
              return $this->redirectToRoute('product', ["id" => $id]);
            }
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
        return $this->render('Product/product-edit.html.twig', [
          'current' => 'products',
          'product' => $product,
          'form'    => $form->createView()
        ]);
    }

    /**
     * @Route("/definition-du-code-barre/{id}", name="product_barecode")
     * @IsGranted("ROLE_ADMIN")
     */
    public function product_barecode(Request $request, EntityManagerInterface $manager, Product $product, int $id)
    {
      if($request->isMethod('post')){
        $data = $request->request->all();
        $token = $data['_csrf_token'];
        $codebarre = $data['codebarre'];
        if($this->isCsrfTokenValid('prices_barecode', $token)){
          $exitingProduct = $manager->getRepository(Product::class)->findOneBy(["code_barre" => $codebarre]);
          if(empty($exitingProduct)){
            $product->setCodeBarre($codebarre);
            $product->setUpdatedAt(new \DateTime());
            $product->setUpdatedBy($this->getUser());
            try{
              $manager->flush();
              $this->addFlash('success', 'Enregistrement du code barre de <strong>'.$product->label().'</strong> (<strong>'.$codebarre.'</strong>) réussi.');
            } 
            catch(\Exception $e){
              $this->addFlash('danger', $e->getMessage());
            }
            return $this->redirectToRoute('product');
          }
          else{
            $this->addFlash('danger', "Code barre <strong>$codebarre</strong> déjà engistré pour le produit <strong>".$exitingProduct->label()."</strong>");
            return $this->redirectToRoute('product_barecode', ["id" => $id]);
          }
        }
      }
      return $this->render('Product/product-barecode.html.twig', [
        'current' => 'products',
        'product' => $product,
      ]);
    }

    /**
     * @Route("/set-code-barre/{id}/{codebarre}", name="set_code_barre", options={"expose"=true}, methods={"POST", "GET"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function set_code_barre(Request $request, EntityManagerInterface $manager, Product $product, $codebarre)
    {
      $exit = $manager->getRepository(Product::class)->findOneBy(["code_barre" => $codebarre]);
      if(empty($exit)){
        return new JsonResponse(true);
      }
      else{
        return new JsonResponse(false);
      }
      $product->setCodeBarre($codebarre);
      try{
        $manager->flush();
        $this->addFlash('success', 'Enregistrement du code barre de <strong>'.$product->label().'</strong> <strong>'.$codebarre.'</strong> réussi.');
        $response = true;
      } 
      catch(\Exception $e){
        $this->addFlash('danger', $e->getMessage());
        $response = false;
      } 
      return new JsonResponse($response);
    }

    /**
     * @Route("/update-product-stock/{id}", name="update_product_stock")
     * @IsGranted("ROLE_STOCK_INITIAL")
     */
    public function update_product_stock(Request $request, EntityManagerInterface $manager, Product $product)
    {
      $ancienStock = $product->getStocks()[0]->getQuantity();
      if($request->isMethod('post'))
      {
        $data = $request->request->all();
        if(!empty($data['token']))
        {
          $token = $data['token'];
          if($this->isCsrfTokenValid('update_product_stock', $token)){
            $data  = $request->request->all();
            $quantity = $data["stock"];
            $motif = $data["motif"];
            if($quantity == null or empty($motif)){
              $this->addFlash('danger', "Formulaire incomplé. Veuillez remplir tous les champs.");
              return $this->redirectToRoute('product');
            }
            $activite = new Activite();
            $activite->setDate(new \DateTime());
            $activite->setUser($this->getUser());
            $activite->setDescription("Mise à jour de stock de ".$product->label()." ".$ancienStock."  => ".$quantity.". Motif: ".$motif);
            $manager->persist($activite);

            $product->getStocks()[0]->setQuantity($quantity);
            $product->getStocks()[0]->setUpdatedAt(new \DateTime());
            $product->getStocks()[0]->setUpdatedBy($this->getUser());

            try{
              $manager->flush();
              $this->addFlash('success', 'Mise à jour du stock de <strong>'.$product->label().' produits</strong> réussie.');
            } 
            catch(\Exception $e){
              $this->addFlash('danger', $e->getMessage());
            } 
            return $this->redirectToRoute('product');
          }
          else{
            $this->addFlash('danger', "Jéton de sécurité invalide. Veuillez rependre l'action.");
          }
        }
      }
      return $this->render('Product/update-product-stock.html.twig', [
        'current' => 'products',
        'product' => $product,
        'stock' => $ancienStock,
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

        // Vérification 4 : Si le prix moyen de vente est supérieur au prix de vente, ça provoquera des erreurs lors d'une vente.
        if($averageSellingPrice > $unitPrice)
        {
          $this->addFlash('danger', 'Impossible de continuer. Le prix moyen de vente ne doit pas être supérieur au prix de vente.');
          return $this->redirectToRoute('define_product_prices', ["id" => $id]);
        }
        // die();
        
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
      return $this->render('Product/define-product-prices.html.twig', [
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

      return $this->render('Product/edit-products-unit-prices.html.twig', [
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
      $html = $this->renderView('Product/inventaire-stock-de-produits.html.twig', [
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

        return $this->render('Product/cout-total-produits.html.twig', [
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
        $html = $this->renderView('Product/impression-cout-total-produits.html.twig', [
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
        $html = $this->renderView('Product/impression-catalogue-produits.html.twig', [
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
        return $this->render('Product/constituer-catalogue-produits.html.twig', [
          'current'    => 'products',
          'products'   => $products,
          'categories' => $categories,
        ]);
    }
}
