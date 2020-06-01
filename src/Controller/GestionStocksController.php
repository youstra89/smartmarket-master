<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Stock;
use App\Entity\Store;
use App\Entity\Product;
use App\Entity\Informations;
use App\Entity\ProviderCommande;
use App\Entity\Approvisionnement;
use App\Entity\DetailsApprovisionnement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/admin/gestion-des-stocks")
 */
class GestionStocksController extends AbstractController
{
    /**
     * @Route("/", name="approvisionnements")
     * @IsGranted("ROLE_DEPOT")
     */
    public function index(EntityManagerInterface $manager)
    {
      $approvisionnements = $manager->getRepository(Approvisionnement::class)->findAll();
      return $this->render('Stock/approvisionnements.html.twig', [
        'current'            => 'stores',
        'approvisionnements' => $approvisionnements
      ]);
    }

    /**
     * @Route("/approvisionner-un-depot-a-partir-d-un-autre/{id}", name="approvisionner_depot")
     * @IsGranted("ROLE_DEPOT")
     */
    public function approvisionner_depot(Request $request, EntityManagerInterface $manager, Store $store, int $id)
    {
      $stocks  = $manager->getRepository(Stock::class)->storeProducts($id);
      $stores = $manager->getRepository(Store::class)->findAll();
      if($request->isMethod('post')){
        $data = $request->request->all();
        $token = $data['token'];
        if($this->isCsrfTokenValid('approvisionner_depot', $token)){
          $date          = new \DateTime($data['date']);
          $quantities    = $data['quantities'];
          $destinationId = $data['destinationId'];
          $destination   = $manager->getRepository(Store::class)->find($destinationId);
          $reference     = "APPRO-".$date->format("ymd")."-".(new \DateTime())->format("His");
          // dd($data);
          $approvisionnement = new Approvisionnement();
          $approvisionnement->setReference($reference);
          $approvisionnement->setStatus("DEMANDEE");
          $approvisionnement->setDate($date);
          $approvisionnement->setSource($store);
          $approvisionnement->setDestination($destination);
          $approvisionnement->setCreatedAt(new \DateTime());
          $approvisionnement->setCreatedBy($this->getUser());
          $manager->persist($approvisionnement);

          foreach ($quantities as $key => $value) {
            $product = $manager->getRepository(Product::class)->find($key);
            $detail = new DetailsApprovisionnement();
            $detail->setApprovisionnement($approvisionnement);
            $detail->setProduct($product);
            $detail->setQuantity($value);
            $detail->setCreatedAt(new \DateTime());
            $detail->setCreatedBy($this->getUser());  
            $manager->persist($detail);
          }
          
          // dd($approvisionnement);
          try{
            $manager->flush();
            $this->addFlash('success', 'Enregistrement de l\'approvisionnement <strong>'.$reference.'</strong> réussie.');
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          } 

          return $this->redirectToRoute('approvisionnements');
        }
        else{
          $this->addFlash('danger', "Formulaire invalide. Veuillez reprendre la procédure. Il semble que le formulaire ait été laissé pendant longtemps actif.");
          return $this->redirectToRoute('approvisionner_depot', ["id" => $id]);
        }
      }

      return $this->render('Stock/approvisionner-depot.html.twig', [
        'current' => 'stores',
        'stores'  => $stores,
        'stocks'  => $stocks,
        'store'   => $store,
      ]);
    }


    /**
     * @Route("/bon-de-sortie/{id}", name="bon_de_sortie", requirements={"id"="\d+"})
     * @param Approvisionnement $approvisionnement
     * @IsGranted("ROLE_DEPOT")
     */
    public function bon_de_sortie(EntityManagerInterface $manager, Approvisionnement $approvisionnement, int $id)
    {
        $info = $manager->getRepository(Informations::class)->find(1);
        
        // dd($commande);

        // Configure Dompdf according to your needs
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        
        // Instantiate Dompdf with our options
        $dompdf = new Dompdf($pdfOptions);
        
        // Retrieve the HTML generated in our twig file
        $html = $this->renderView('Stock/bon-de-sortie.html.twig', [
            'info'              => $info,
            'approvisionnement' => $approvisionnement,
        ]);
        
        // Load HTML to Dompdf
        $dompdf->loadHtml($html);
        //"dompdf/dompdf": "^0.8.3",
        
        // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();

        // Output the generated PDF to Browser (force download)
        $dompdf->stream("bon-de-sortie-".$approvisionnement->getReference().".pdf", [
            "Attachment" => false
        ]);
    }
          

    /**
     * @Route("/reception-des-produits-d-un-approvissionnement/{id}", name="receive_approvisionnement")
     * @param Approvisionnement $approvisionnement
     * @IsGranted("ROLE_DEPOT")
     */
    public function receive_approvisionnement(Request $request, EntityManagerInterface $manager, Approvisionnement $approvisionnement, int $id)
    {
      if ($approvisionnement->getStatus() == "RECU") {
        $this->addFlash('warning', 'Cet approvisionnement a déjà été reçu.');
        return $this->redirectToRoute('approvisionnements');
      }

      if($request->isMethod('post')){
        $data = $request->request->all();
        $token = $data['token'];
        $date  = new \DateTime($data['date']);
        if($this->isCsrfTokenValid('reception_approvisionnement', $token)){
          foreach ($approvisionnement->getDetailsApprovisionnements() as $value) {
            $quantity         = $value->getQuantity();
            $productId        = $value->getProduct()->getId();
            $productLabel     = $value->getProduct()->label();
            $stockSource      = $this->stock_source_ou_stock_destination($approvisionnement, $productId, 1);
            $stockDestination = $this->stock_source_ou_stock_destination($approvisionnement, $productId, 2);
            if($quantity > $stockSource->getQuantity()){
              $this->addFlash('warning', 'La quantité demandé ne peut être servie pour le produit <strong>'.$productLabel.'</strong>.');
              return $this->redirectToRoute('receive_approvisionnement', ["id" => $id]);      
            }
            else{
              $approvisionnement->setDateReception($date);
              $stockSource->setQuantity($stockSource->getQuantity() - $quantity);
              $stockDestination->setQuantity($stockDestination->getQuantity() + $quantity);
            }
          }
          $approvisionnement->setStatus("RECU");
          try{
            $manager->flush();
            $this->addFlash('success', 'Enregistrement de la réception de l\'approvisionnement <strong>'.$approvisionnement->getReference().'</strong> réussie.');
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          }
          return $this->redirectToRoute('approvisionnements');
        }
      }

      return $this->render('Stock/receive-approvisionnement.html.twig', [
        'current'           => 'stores',
        'approvisionnement' => $approvisionnement
      ]);
    }

    public function stock_source_ou_stock_destination(Approvisionnement $approvisionnement, int $productId, int $type)
    {
      // La fonction va nous permettre de sélectionner soit la source soit la destination d'un approvisionnement. La variable $type désigne la source
      // lorsqu'elle vaut 1 et destination lorsqu'elle vaut 2.
      if ($type === 1) {
        foreach ($approvisionnement->getSource()->getStocks() as $value) {
          if($value->getProduct()->getId() == $productId)
            $stock = $value;
        }
      }
      elseif ($type === 2) {
        foreach ($approvisionnement->getDestination()->getStocks() as $value) {
          if($value->getProduct()->getId() == $productId)
            $stock = $value;
        }
      }
      return $stock;
    }
}
