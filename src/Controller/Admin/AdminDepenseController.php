<?php

namespace App\Controller\Admin;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Depense;
use App\Form\DepenseType;
use App\Entity\Settlement;
use App\Entity\Informations;
use App\Entity\ComptaExercice;
use App\Entity\CustomerCommande;
use App\Service\CheckConnectedUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\FonctionsComptabiliteController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/admin/depenses")
 * @Security("has_role('ROLE_COMPTABLE')")
 */
class AdminDepenseController extends AbstractController
{
    /**
     * @Route("/", name="depenses")
     */
    public function index(EntityManagerInterface $manager, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        return $this->render('Admin/Depense/index.html.twig', [
          'current'    => 'accounting',
        ]);
    }

    /**
     * @Route("/add", name="depenses_add")
     */
    public function add(Request $request, EntityManagerInterface $manager, FonctionsComptabiliteController $fonctions)
    {
        $depense = new Depense();
        $exercice  = $manager->getRepository(ComptaExercice::class)->dernierExerciceEnCours();
        $form = $this->createForm(DepenseType::class, $depense);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
          $data = $request->request->all();
          if(isset($data['date']))
          {
            $date = new \DateTime($data['date']);
            $mode = (int) $data['mode'];
            $mois = $date->format("Y-m");
            $depense->setDateDepense($date);
            $depense->setCreatedAt(new \DateTime());
            $depense->setCreatedBy($this->getUser());
            $manager->persist($depense);
            try{
              $fonctions->ecritureDeDepensesDansLeJournalComptable($manager, $depense->getAmount(), $mode, $depense, $depense->getDescription(), $exercice);
              $manager->flush();
              $this->addFlash('success', 'Enregistrement de dépense <strong>'.$depense->getDescription().'</strong> réussie.');
              return $this->redirectToRoute('depenses_du_mois', ["mois" => $mois]);
            } 
            catch(\Exception $e){
              $this->addFlash('danger', $e->getMessage());
              return $this->redirectToRoute('accounting');
            } 
          }
          else{
            $this->addFlash('danger', "Le champ date de dépense ne doit pas être vide.");
            return $this->redirectToRoute('depenses_add');
          }
        }
          
        return $this->render('Admin/Depense/depense-add.html.twig', [
          'current' => 'accounting',
          'form'    => $form->createView()
        ]);
    }

    /**
     * @Route("/depenses-journalieres-du-mois", name="depenses_du_mois")
     */
    public function depenses_du_mois(Request $request, EntityManagerInterface $manager, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $mois = $request->get('mois');
        if(empty($mois))
          $mois = (new \DateTime())->format('Y-m');
        $depenses = $manager->getRepository(Depense::class)->depensesDuMois($mois);
        if(empty($depenses)){
          $this->addFlash('error', 'La date sélectionnée n\'est pas correcte.');
          return $this->redirectToRoute('depenses_mensuelles');
        }
        if (setlocale(LC_TIME, 'fr_FR') == '') {
          // setlocale(LC_TIME, 'FRA');  //correction problème pour windows
            $format_jour = '%#d';
        } else {
            $format_jour = '%e';
        }
        setlocale (LC_TIME, 'fr_FR.utf8','fra'); 
        $date = strftime("%d %B %Y", strtotime($mois));
        $mois = ucfirst(substr($date, 3));
        
        return $this->render('Admin/Depense/depenses-journalieres-du-mois.html.twig', [
          'mois'  => $mois,
          'depenses'  => $depenses,
          'current' => 'accounting',
        ]);
    }

    /**
     * @Route("/depenses-mensuelles", name="depenses_mensuelles")
     */
    public function depenses_mensuelles(EntityManagerInterface $manager, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $repoDepense = $manager->getRepository(Depense::class);
        $mois  = $repoDepense->differentDates();
        $gains = [];
        foreach ($mois as $key => $value) {
          $ventes = $repoDepense->monthSells($value['date']);
          $somme = 0;
          foreach ($ventes as $keyV => $valueV) {
            $somme += $valueV['somme'];
          }
          $gains[$value['date']] = $somme;
        }
        return $this->render('Admin/Depense/depense-mensuelle.html.twig', [
          'mois'    => $mois,
          'gains'   => $gains,
          'current' => 'accounting',
        ]);
    }

    /**
     * @Route("/comptabilite-les-debits", name="accountingdebtor")
     */
    public function debits(EntityManagerInterface $manager, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $repoCustomerCommande  = $manager->getRepository(CustomerCommande  ::class);
        $repoSettlement = $manager->getRepository(Settlement::class);
        $commandes     = $repoCustomerCommande->lesDebiteurs();
        $reglements    = $repoSettlement->reglementsIncomplets();
        // dump($reglements);
        return $this->render('Admin/Depense/comptabilite-debit.html.twig', [
          'ventes'  => $commandes,
          'reglements'  => $reglements,
          'current'    => 'accounting',
        ]);
    }


    /**
     * @Route("/liste-des-depenses-menseulles", name="print_depenses_mensuelles")
     */
    public function liste_des_depenses_menseulles(EntityManagerInterface $manager, Request $request)
    {
        $info = $manager->getRepository(Informations::class)->find(1);
        
        $mois = $request->get('mois');
        // dd($commande);
        if (setlocale(LC_TIME, 'fr_FR') == '') {
          $format_jour = '%#d';
        } else {
          $format_jour = '%e';
        }
        setlocale (LC_TIME, 'fr_FR.utf8','fra'); 
        $date = strftime("%d %B %Y", strtotime($mois));

        // dump(utf8_encode(strftime("%A $format_jour %B %Y", strtotime('2008-04-18'))));
        // dump(strftime("%a $format_jour %b %Y", strtotime('2008-04-18')));
        $depenses = $manager->getRepository(Depense::class)->depensesDuMois($mois);
        $mois = ucfirst(substr($date, 3));

        // Configure Dompdf according to your needs
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        
        // Instantiate Dompdf with our options
        $dompdf = new Dompdf($pdfOptions);
        
        // Retrieve the HTML generated in our twig file
        $html = $this->renderView('Admin/Depense/impression-depenses-mensuelles.html.twig', [
            'info'     => $info,
            'mois'     => $mois,
            'depenses' => $depenses,
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
        $dompdf->stream("depenses-".$mois.".pdf", [
            "Attachment" => false
        ]);
    }
}
