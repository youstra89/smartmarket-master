<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use NumberFormatter;
use App\Entity\Avoir;
use App\Entity\Informations;
use Dompdf\Dompdf;
use Dompdf\Options;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\FonctionsComptabiliteController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/les-factures-avoir")
 */
class AdminAvoirController extends AbstractController
{
    /**
     * @Route("/", name="avoir")
     * @IsGranted("ROLE_VENTE")
     */
    public function index(Request $request, EntityManagerInterface $manager, FonctionsComptabiliteController $fonctions)
    {
      $exercice  = $fonctions->exercice_en_cours($manager);
      $exerciceId = $exercice->getId();
      $avoirs = $manager->getRepository(Avoir::class)->avoirDeLExercice($exerciceId);
      // dump($avoirs[0]);

      return $this->render('Avoir/index.html.twig', [
        'current' => 'avoir',
        'avoirs'   => $avoirs
      ]);
    }

    /**
     * @Route("/impression-de-ticket-avoir/{id}", name="ticket_avoir", requirements={"id"="\d+"})
     * @param Avoir $avoir
     * @IsGranted("ROLE_VENTE")
     */
    public function ticket_avoir(EntityManagerInterface $manager, Avoir $avoir)
    {
      $info = $manager->getRepository(Informations::class)->find(1);
      // Configure Dompdf according to your needs
      $pdfOptions = new Options();
      $pdfOptions->set('defaultFont', 'Arial');
      
      // Instantiate Dompdf with our options
      $dompdf = new Dompdf($pdfOptions);
      
      $html = $this->renderView('Avoir/ticket-avoir.html.twig', [
          'info'   => $info,
          'avoir' => $avoir,
      ]);
      
      // Load HTML to Dompdf
      $dompdf->loadHtml($html);
      // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
      // $dompdf->setPaper('A8', 'portrait');
      $dompdf->setPaper('A8', 'landscape');

      $orientation = "landscape";
      $nbr   = count($avoir->getDetailsAvoirs());
      $nbr   = $nbr * 25 + 350;
      $paper = [0, 0, $nbr, 240];
      // dd($paper);
      $dompdf->setPaper($paper, $orientation);

      // Render the HTML as PDF
      $dompdf->render();

      //File name
      $filename = "ticket-avoir-".$avoir->getCommande()->getReference();

      // Output the generated PDF to Browser (force download)
      $dompdf->stream($filename.".pdf", [
          "Attachment" => false
      ]);
    }
}
