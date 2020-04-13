<?php

namespace App\Controller\Admin;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Settlement;
use App\Entity\Informations;
use App\Entity\CustomerCommande;
use App\Entity\ProviderCommande;
use App\Entity\ProviderSettlement;
use App\Service\CheckConnectedUser;
use App\Entity\CustomerCommandeDetails;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/admin/comptabilite")
 * @Security("has_role('ROLE_COMPTABLE')")
 */
class AdminAccountingController extends AbstractController
{
    /**
     * @Route("/", name="accounting")
     */
    public function index(ObjectManager $manager, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        return $this->render('Admin/Accounting/index.html.twig', [
          'current'    => 'accounting',
        ]);
    }

    /**
     * @Route("/ventes-du-jour/{date}", name="ventes_du_jour")
     */
    public function vente_du_jour(ObjectManager $manager, $date, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $ventes = $manager->getRepository(CustomerCommande::class)->dayCommande($date);

        $dateVente = $this->dateEnFrancais($date);;
        // return new Response(var_dump($ventes));
        if(empty($ventes)){
          $this->addFlash('danger', 'La date saisie n\'est pas correcte.');
          return $this->redirectToRoute('dayly.accounting');
        }
        return $this->render('Admin/Accounting/ventes-du-jour.html.twig', [
          'ventes'  => $ventes,
          'dateVente'    => $dateVente,
          'current' => 'accounting',
        ]);
    }


    /**
     * @Route("/entrees-du-jour/{date}", name="entrees_du_jour")
     */
    public function entrees_du_jour(ObjectManager $manager, $date, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $entrees = $manager->getRepository(Settlement::class)->versementsDuJour($date);

        $dateVente = $this->dateEnFrancais($date);;
        // return new Response(var_dump($ventes));
        if(empty($entrees)){
          $this->addFlash('danger', 'La date saisie n\'est pas correcte.');
          return $this->redirectToRoute('dayly.accounting');
        }
        return $this->render('Admin/Accounting/entrees-du-jour.html.twig', [
          'entrees'  => $entrees,
          'dateVente'    => $dateVente,
          'current' => 'accounting',
        ]);
    }

    /**
     * @Route("/comptabilite-journaliere", name="dayly.accounting")
     */
    public function comptabilite_journaliere(Request $request, ObjectManager $manager, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $mois = $request->get('mois');
        if(empty($mois))
          $mois = (new \DateTime())->format('Y-m');
        $ventes = $manager->getRepository(CustomerCommande::class)->monthlySelling($mois);
        $entrees = $manager->getRepository(Settlement::class)->entreesDuMois($mois);
        $benefices = $manager->getRepository(CustomerCommandeDetails::class)->benefice_journalier($mois);
        // dump($entrees);
        if(empty($ventes)){
          $this->addFlash('error', 'La date sélectionnée n\'est pas correcte.');
          // return $this->redirectToRoute('dayly.accounting');
        }

        $dates = $this->differentesDates($ventes, $entrees);
        $mois = $this->dateEnFrancais($mois, false);

        return $this->render('Admin/Accounting/comptabilite-journaliere.html.twig', [
          'dates'     => $dates,
          'ventes'    => $ventes,
          'entrees'   => $entrees,
          'mois'      => $mois,
          'benefices' => $benefices,
          'current'   => 'accounting',
        ]);
    }


    public function differentesDates($ventes, $entrees)
    {
      $dates = [];
      foreach ($ventes as $key => $value) {
        $dates[] = $value["date"]->format("Y-m-d");
      }
      foreach ($entrees as $key => $value) {
        $dates[] = $value["date"]->format("Y-m-d");
      }
      return array_unique($dates);
    }

    /**
     * @Route("/comptabilite-mensuelle", name="monthly.accounting")
     */
    public function comptabilite_mensuelle(ObjectManager $manager, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $repoCommande = $manager->getRepository(CustomerCommande::class);
        $mois  = $repoCommande->differentDates();
        $gains = [];
        $entrees = [];
        $benefices = [];
        foreach ($mois as $key => $value) {
          $mois[$key]['nomFr'] = $this->dateEnFrancais($value['date'], false);
          $ventes = $repoCommande->monthSells($value['date']);
          $somme = 0;
          foreach ($ventes as $keyV => $valueV) {
            $somme += $valueV['somme'];
          }

          // Total des entrées du mois
          $entreesDunMois = $manager->getRepository(Settlement::class)->entreesDuMois($value['date']);
          $sommeEntrees   = 0;
          foreach ($entreesDunMois as $keyV => $valueV) {
            $sommeEntrees += $valueV['entree'];
          }
          $entrees[$value['date']]   = $sommeEntrees;
          $benefices[$value['date']] = $manager->getRepository(CustomerCommandeDetails::class)->benefice_mensuel($value['date']);
          $gains[$value['date']]     = $somme;
        }
        
        return $this->render('Admin/Accounting/comptabilite-mensuelle.html.twig', [
          'mois'      => $mois,
          'gains'     => $gains,
          'entrees'   => $entrees,
          'benefices' => $benefices,
          'current'   => 'accounting',
        ]);
    }

    public function dateEnFrancais($date, bool $jour = true)
    {
      // Cette fonction me permet de convertir la date reçu en paramètre en français
      /**
       * Elle reçoit deux paramètres
       *      ----- Paramètre 1
       *    - Le premier est la date à convertir
       * 
       *      ----- Paramètre 2
       *    - Le deuxième est le type de retour qui peut être soit en mois (ex: Mars 2020) ou en jour (ex: Vendredi 20 Mars 2020)
       *      Ce deuxième paramètre est de type booléen et facultatif. S'il est à true (qui est d'ailleurs sa valeur par défaut) 
       *      alors le retour sera en jour. Sinon, il est en mois.
       */
      if (setlocale(LC_TIME, 'fr_FR') == '') {
        $format_jour = '%#d';
      } else {
        $format_jour = '%e';
      }
      setlocale (LC_TIME, 'fr_FR.utf8','fra'); 

      if($jour == true)
      {
        $date = $date instanceof DateTime ? $date->format("Y-m-d") : $date;
        $date = utf8_encode(strftime("%A $format_jour %B %Y", strtotime($date)));
        $dateEnFrancais = ucwords($date);
      }
      else{
        $mois = utf8_encode(strftime("$format_jour %B %Y", strtotime($date)));
        $dateEnFrancais = ucfirst(substr($mois, 3));
      }

      return $dateEnFrancais;
    }

    /**
     * @Route("/comptabilite-clients-débiteurs", name="accounting.debtor")
     */
    public function debits(ObjectManager $manager, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $repoCustomerCommande = $manager->getRepository(CustomerCommande::class);
        $repoSettlement       = $manager->getRepository(Settlement      ::class);
        $commandes            = $repoCustomerCommande->lesDebiteurs();
        $nbrCommandes         = $repoCustomerCommande->nombreCommandesDesDebiteurs();
        $reglements           = $repoSettlement->reglementsIncomplets();
        $restesAPayer         = $repoSettlement->restesAPayer();
        $restes               = [];
        $customers            = [];
        // dd($restesAPayer);
        
        // On va sélectionner les différents clients
        foreach ($commandes as $value) {
          $customer               = $value->getCustomer();
          $customerId             = $customer->getId();
          $customers[$customerId] = $customer;
        }
        
        foreach ($restesAPayer as $value) {
          $restes[$value["id"]] = $value["reste"];
        }
        // dump($restesAPayer);
        return $this->render('Admin/Accounting/comptabilite-debit.html.twig', [
          'current'      => 'accounting',
          'ventes'       => $commandes,
          'nbrCommandes' => $nbrCommandes,
          'restesAPayer' => $restes,
          'customers'    => $customers,
          'reglements'   => $reglements,
        ]);
    }

    /**
     * @Route("/comptabilite-fournisseurs-creanciers", name="accounting_creance")
     */
    public function creances(ObjectManager $manager, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $repoProviderCommande = $manager->getRepository(ProviderCommande::class);
        $repoSettlement       = $manager->getRepository(ProviderSettlement::class);
        $commandes            = $repoProviderCommande->lesCreanciers();
        $reglements           = $repoSettlement->reglementsIncomplets();
        $nbrCommandes         = $repoProviderCommande->nombreCommandesDesCreanciers();
        $restesAPayer         = $repoSettlement->restesAPayer();
        $restes               = [];
        $providers            = [];
        // On va sélectionner les différents clients
        foreach ($commandes as $value) {
          $provider               = $value->getProvider();
          $providerId             = $provider->getId();
          $providers[$providerId] = $provider;
        }
        
        foreach ($restesAPayer as $value) {
          $restes[$value["id"]] = $value["reste"];
        }
        // dump($providers);
        return $this->render('Admin/Accounting/comptabilite-credit.html.twig', [
          'achats'       => $commandes,
          'reglements'   => $reglements,
          'current'      => 'accounting',
          'nbrCommandes' => $nbrCommandes,
          'restesAPayer' => $restes,
          'providers'    => $providers,
        ]);
    }


    /**
     * @Route("/impression-des-dettes", name="print_dettes")
     */
    public function impression_des_dettes(ObjectManager $manager)
    {
        $info = $manager->getRepository(Informations::class)->find(1);
        $repoCustomerCommande = $manager->getRepository(CustomerCommande::class);
        $repoSettlement       = $manager->getRepository(Settlement      ::class);
        $commandes            = $repoCustomerCommande->lesDebiteurs();
        $nbrCommandes         = $repoCustomerCommande->nombreCommandesDesDebiteurs();
        $reglements           = $repoSettlement->reglementsIncomplets();
        $restesAPayer         = $repoSettlement->restesAPayer();
        $restes               = [];
        $customers            = [];
        // dd($restesAPayer);
        
        // On va sélectionner les différents clients
        foreach ($commandes as $value) {
          $customer               = $value->getCustomer();
          $customerId             = $customer->getId();
          $customers[$customerId] = $customer;
        }
        
        foreach ($restesAPayer as $value) {
          $restes[$value["id"]] = $value["reste"];
        }
       
        $date = $this->dateEnFrancais((new \DateTime())->format("Y-m-d"));;

        // Configure Dompdf according to your needs
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        
        // Instantiate Dompdf with our options
        $dompdf = new Dompdf($pdfOptions);
        
        // Retrieve the HTML generated in our twig file
        $html = $this->renderView('Admin/Accounting/impression-dettes.html.twig', [
          'info'         => $info,
          'date'         => $date,
          'current'      => 'accounting',
          'ventes'       => $commandes,
          'nbrCommandes' => $nbrCommandes,
          'restesAPayer' => $restes,
          'customers'    => $customers,
          'reglements'   => $reglements,
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
        $dompdf->stream("creances.pdf", [
            "Attachment" => false
        ]);
    }


    /**
     * @Route("/impression-des-creances", name="print_creances")
     */
    public function impression_des_creances(ObjectManager $manager)
    {
        $info = $manager->getRepository(Informations::class)->find(1);
        $repoProviderCommande = $manager->getRepository(ProviderCommande::class);
        $repoSettlement       = $manager->getRepository(ProviderSettlement::class);
        $commandes            = $repoProviderCommande->lesCreanciers();
        $reglements           = $repoSettlement->reglementsIncomplets();
        $nbrCommandes         = $repoProviderCommande->nombreCommandesDesCreanciers();
        $restesAPayer         = $repoSettlement->restesAPayer();
        $restes               = [];
        $providers            = [];
        // On va sélectionner les différents clients
        foreach ($commandes as $value) {
          $provider               = $value->getProvider();
          $providerId             = $provider->getId();
          $providers[$providerId] = $provider;
        }
        
        foreach ($restesAPayer as $value) {
          $restes[$value["id"]] = $value["reste"];
        }

        $date = $this->dateEnFrancais((new \DateTime())->format("Y-m-d"));;

        // Configure Dompdf according to your needs
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        
        // Instantiate Dompdf with our options
        $dompdf = new Dompdf($pdfOptions);
        
        // Retrieve the HTML generated in our twig file
        $html = $this->renderView('Admin/Accounting/impression-creances.html.twig', [
          'info'         => $info,
          'date'         => $date,
          'achats'       => $commandes,
          'reglements'   => $reglements,
          'current'      => 'accounting',
          'nbrCommandes' => $nbrCommandes,
          'restesAPayer' => $restes,
          'providers'    => $providers,
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
        $dompdf->stream("creances.pdf", [
            "Attachment" => false
        ]);
    }
}
