<?php

namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Avoir;
use App\Entity\Acompte;
use App\Entity\Customer;
use App\Entity\Cloture;
use App\Entity\Depense;
use App\Entity\Settlement;
use App\Entity\Informations;
use App\Entity\ComptaExercice;
use App\Entity\RetraitAcompte;
use App\Entity\CustomerCommande;
use App\Entity\ProviderCommande;
use App\Entity\ProviderSettlement;
use App\Service\CheckConnectedUser;
use App\Entity\CustomerCommandeDetails;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\FonctionsComptabiliteController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
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
    public function index(EntityManagerInterface $manager, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        return $this->render('Accounting/index.html.twig', [
          'current'    => 'accounting',
        ]);
    }

    /**
     * @Route("/ventes-du-jour/{date}", name="ventes_du_jour")
     */
    public function vente_du_jour(EntityManagerInterface $manager, $date, CheckConnectedUser $checker)
    {
      if($checker->getAccess() == true)
        return $this->redirectToRoute('login');

      $ventes = $manager->getRepository(CustomerCommande::class)->venteDuJour($date);

      $dateVente = $this->dateEnFrancais($date);
      // dd($ventes);
      if(empty($ventes)){
        $this->addFlash('danger', 'La date saisie n\'est pas correcte.');
        return $this->redirectToRoute('compte_journalier');
      }
      return $this->render('Accounting/ventes-du-jour.html.twig', [
        'ventes'  => $ventes,
        'dateVente'    => $dateVente,
        'current' => 'accounting',
      ]);
    }


    /**
     * @Route("/entrees-du-jour/{date}", name="entrees_du_jour")
     */
    public function entrees_du_jour(EntityManagerInterface $manager, $date, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $entrees = $manager->getRepository(Settlement::class)->versementsDuJour($date);

        $dateVente = $this->dateEnFrancais($date);;
        // return new Response(var_dump($ventes));
        if(empty($entrees)){
          $this->addFlash('danger', 'La date saisie n\'est pas correcte.');
          return $this->redirectToRoute('compte_journalier');
        }
        return $this->render('Accounting/entrees-du-jour.html.twig', [
          'entrees'  => $entrees,
          'dateVente'    => $dateVente,
          'current' => 'accounting',
        ]);
    }

    /**
     * @Route("/comptabilite-journaliere", name="compte_journalier")
     */
    public function comptabilite_journaliere(Request $request, EntityManagerInterface $manager, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $mois = $request->get('mois');
        if(empty($mois))
          $mois = (new \DateTime())->format('Y-m');
        $ventes         = $manager->getRepository(CustomerCommande       ::class)->montantTotalHorsTaxeDeToutesLesVenteDUnMois($mois);
        $tousLesAchats  = $manager->getRepository(ProviderCommande       ::class)->montant_net_a_payer_de_tous_les_achats_de_la_date($mois);
        $totalNetAPayer = $manager->getRepository(CustomerCommande       ::class)->montant_net_a_payer_de_toutes_les_ventes_de_la_date($mois);
        $totalRemises   = $manager->getRepository(CustomerCommande       ::class)->totalDesRemiseDeToutesLesVenteDUnMois($mois);
        $entrees        = $manager->getRepository(Settlement             ::class)->entreesDuMois($mois);
        $clotures       = $manager->getRepository(Cloture                ::class)->jours_clotures($mois);
        $benefices      = $manager->getRepository(CustomerCommandeDetails::class)->benefice_journalier($mois);

        $dates = $this->differentesDates($ventes, $entrees, $tousLesAchats);
        $mois  = $this->dateEnFrancais($mois, false);

        return $this->render('Accounting/comptabilite-journaliere.html.twig', [
          'dates'          => $dates,
          'ventes'         => $ventes,
          'entrees'        => $entrees,
          'tousLesAchats'  => $tousLesAchats,
          'totalNetAPayer' => $totalNetAPayer,
          'totalRemises'   => $totalRemises,
          'mois'           => $mois,
          'benefices'      => $benefices,
          'clotures'       => $clotures,
          'current'        => 'accounting',
        ]);
    }


    public function differentesDates($ventes, $entrees, $tousLesAchats)
    {
      $dates = [];
      foreach ($tousLesAchats as $key => $value) {
        $dates[] = $value["date"]->format("Y-m-d");
      }
      foreach ($ventes as $key => $value) {
        $dates[] = $value["date"]->format("Y-m-d");
      }
      foreach ($entrees as $key => $value) {
        $dates[] = $value["date"]->format("Y-m-d");
      }
      return array_values(array_unique($dates));
    }



    /**
     * @Route("/comptabilite-mensuelle", name="compte_mensuel")
     */
    public function comptabilite_mensuelle(EntityManagerInterface $manager, CheckConnectedUser $checker)
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
        
        return $this->render('Accounting/comptabilite-mensuelle.html.twig', [
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
     * @Route("/comptabilite-creances-clients", name="accounting_creance")
     */
    public function creances(EntityManagerInterface $manager, CheckConnectedUser $checker)
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
        return $this->render('Accounting/comptabilite-creances.html.twig', [
          'current'      => 'accounting',
          'ventes'       => $commandes,
          'nbrCommandes' => $nbrCommandes,
          'restesAPayer' => $restes,
          'customers'    => $customers,
          'reglements'   => $reglements,
        ]);
    }

    /**
     * @Route("/comptabilite-arrieres-fournisseurs", name="accounting_arriere")
     */
    public function arrieres(EntityManagerInterface $manager, CheckConnectedUser $checker)
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
        return $this->render('Accounting/comptabilite-arrieres.html.twig', [
          'achats'       => $commandes,
          'reglements'   => $reglements,
          'current'      => 'accounting',
          'nbrCommandes' => $nbrCommandes,
          'restesAPayer' => $restes,
          'providers'    => $providers,
        ]);
    }


    /**
     * @Route("/bilan-du-jour/{date}", name="bilan_du_jour")
     */
    public function bilan_du_jour(EntityManagerInterface $manager, $date)
    {
      $info           = $manager->getRepository(Informations    :: class)->find(1);
      $settlements    = $manager->getRepository(Settlement      :: class)->toutesLesEntreesDuJour($date);
      $depensesDuJour = $manager->getRepository(Depense      :: class)->depense_mensuelle($date);
      $totalNetAPayer = $manager->getRepository(CustomerCommande::class)->montant_net_a_payer_de_toutes_les_ventes_de_la_date($date);
      $avoirs = $manager->getRepository(Avoir::class)->avoirsDuJour($date);

      $totalCreances = 0;
      $date          = new \DateTime($date);
      $commandes     = $manager->getRepository(CustomerCommande::class)->creances_accordees($date);
      foreach ($commandes as $commande) {
        if(isset($commande->getSettlements()[0]))
        {
          $settlement = $commande->getSettlements()[0];
          if($settlement->getDate() == $date and $commande->getNetAPayer() > $settlement->getAmount()){
            $creances[] = $settlement;
            $totalCreances = $totalCreances + $commande->getNetAPayer() - $settlement->getAmount();
          }
        }
      }

      $totalAcomptesRecus = 0;
      $acomptesRecus     = $manager->getRepository(Acompte::class)->acomptesDuJour($date->format("Y-m-d"));
      foreach ($acomptesRecus as $item) {
        $totalAcomptesRecus = $totalAcomptesRecus + $item->getMontant();
      }

      /************************************* Début du calcul du reste des créances du jour */
      // Pour calculer le reste des créances accordées d'un jour, on va faire la somme des créances accordées $sommeCreancesAccordees. 
      // Puis on fait la somme des créances du jour qui ont été règlées $sommeCreancesDuJourReglees.
      // On obtient le résultat en faisant la soustraction de $sommeCreancesAccordees - $sommeCreancesDuJourReglees
      $sommeCreancesAccordees = 0;
      $commandes              = $manager->getRepository(CustomerCommande::class)->creances_accordees($date);
      foreach ($commandes as $commande) {
        if(isset($commande->getSettlements()[0]))
        {
          $settlement = $commande->getSettlements()[0];
          if($settlement->getDate() == $date and $commande->getNetAPayer() > $settlement->getAmount()){
            $sommeCreancesAccordees = $sommeCreancesAccordees + $commande->getNetAPayer() - $settlement->getAmount();
          }
        }
      }


      $sommeCreancesDuJourReglees = 0;
      $commandes                  = $manager->getRepository(CustomerCommande::class)->creances_reglees($date);
      foreach ($commandes as $commande) {
        if(count($commande->getSettlements()) > 1){
          $reglements = $commande->getSettlements();
          foreach ($reglements as $key => $value) {
            if($value->getDate() == $date and $value->getAmount() > 0 and $key != 0 and $value->getCommande()->getDate() == $date){
              $sommeCreancesDuJourReglees = $sommeCreancesDuJourReglees + $value->getAmount();
            }
          }
        }
      }

      // dd($date, $sommeCreancesAccordees, $sommeCreancesDuJourReglees);
      /************************************* Fin du calcul du reste des créances du jour */
      
      //dd($creances, $totalCreances);
      $date = $date->format("Y-m-d");
      $date = $this->dateEnFrancais($date);

      $totalEntrees  = 0;
      $totalCaisse   = 0;
      $totalBanque   = 0;
      $totalAcompte  = 0; 
      $totalSM       = 0;
      foreach ($settlements as $value) {
        $mode = $value->getModepaiement();
        $montant = $value->getAmount();
        if($mode == 1)
        $totalCaisse = $totalCaisse + $montant;
        if($mode == 2)
        $totalBanque = $totalBanque + $montant;
        if($mode == 3)
        $totalAcompte = $totalAcompte + $montant;
        if($mode == 4)
        $totalSM = $totalSM + $montant;
      }
      $totalEntrees = $totalCaisse + $totalBanque + $totalSM;

      $totalAvoirs = 0;
      foreach ($avoirs as $value) {
        $totalAvoirs = $totalAvoirs + $value->getMontant();
      }


      
      // Configure Dompdf according to your needs
      $pdfOptions = new Options();
      $pdfOptions->set('defaultFont', 'Arial');
      
      // Instantiate Dompdf with our options
      $dompdf = new Dompdf($pdfOptions);
      
      // Retrieve the HTML generated in our twig file
      $html = $this->renderView('Accounting/bilan-du-jour.html.twig', [
        'current'                    => 'accounting',
        'info'                       => $info,
        'date'                       => $date,
        'totalAvoirs'                => $totalAvoirs,
        'totalCaisse'                => $totalCaisse,
        'totalBanque'                => $totalBanque,
        'totalAcompte'               => $totalAcompte,
        'totalEntrees'               => $totalEntrees,
        'totalCreances'              => $totalCreances,
        'totalAcomptesRecus'         => $totalAcomptesRecus,
        'sommeCreancesAccordees'     => $sommeCreancesAccordees,
        'sommeCreancesDuJourReglees' => $sommeCreancesDuJourReglees,
        'totalSM'                    => $totalSM,
        'depensesDuJour'             => $depensesDuJour[0]["somme"],
        'settlements'                => $settlements,
        'totalNetAPayer'             => $totalNetAPayer[0][1],
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
      $dompdf->stream("point-des-entrees-et-sortie-du".$date.".pdf", [
          "Attachment" => false
      ]);
    }

    
    /**
     * @Route("/cloturer-les-activites-du-jour/{date}", name="cloturer_activites_du_jour")
     */
    public function cloturer_activites_du_jour(Request $request, EntityManagerInterface $manager, $date, FonctionsComptabiliteController $fonctions)
    {
      $token = $request->get('_csrf_token');
      $dateCloture = new \DateTime($date);
      $cloture = $manager->getRepository(Cloture::class)->findOneByDate($dateCloture);
      if(!empty($cloture)){
        $this->addFlash('danger', 'Les activités du <strong>'.$date.'</strong> ont déjà été clôturées.');
        return $this->redirectToRoute('compte_journalier');
      }

      if($this->isCsrfTokenValid('cloturer_activites', $token))
      {
        $ecrituresComptables = [];
        $settlements            = $manager->getRepository(Settlement        ::class)->toutesLesEntreesDuJour($date);
        $reglementsFournisseurs = $manager->getRepository(ProviderSettlement::class)->toutesLesReglementsFournisseursDuJour($date);
        $depensesDuJour         = $manager->getRepository(Depense           ::class)->depensesDuMois($date);
        // $totalDesVentes         = $manager->getRepository(CustomerCommande  ::class)->montant_net_a_payer_de_toutes_les_ventes_de_la_date($date);
        $ventesDUnJour          = $manager->getRepository(CustomerCommande  ::class)->toutes_les_ventes_du($date);
        // $totalDesAchats         = $manager->getRepository(ProviderCommande  ::class)->montant_net_a_payer_de_toutes_les_achats_de_la_date($date);
        $achatsDUnJour          = $manager->getRepository(ProviderCommande  ::class)->tous_les_achats_du($date);
        $acomptes               = $manager->getRepository(Acompte           ::class)->acomptesDuJour($date);
        $retraitsAcompte        = $manager->getRepository(RetraitAcompte    ::class)->retraitsAcomptesDuJour($date);
        $avoirs                 = $manager->getRepository(Avoir             ::class)->avoirsDuJour($date);
        $exercice               = $manager->getRepository(ComptaExercice    ::class)->trouverExerciceDUneDate($date)[0];
        $dateFr = $this->dateEnFrancais($date);

        // Total des créances du jour, des bénéfices et des remises
        $totalCreances  = 0;
        $totalBenefices = 0;
        $totalRemises   = 0;
        $totalDesVentes = 0;
        foreach ($ventesDUnJour as $vente) {
          $$totalDesVentes = $totalDesVentes + $vente->getNetAPayer();
          $$totalRemises   = $totalRemises + $vente->getRemise();
          $$totalCreances  = $totalCreances + $vente->getNetAPayer() - $vente->getTotalSettlments();
          $$totalBenefices = $totalBenefices + $vente->getTotalBenefices();

          $ecrituresDeVente     = $fonctions->ecriture_de_vente_dans_le_journal_comptable($manager, $vente->getNetAPayer(), $vente->getTotalBenefices(), $vente->getTva(), $exercice, $vente->getDate(), $vente);
          $ecrituresDeResultats = $fonctions->ecriture_du_resultat_de_vente_journal_comptable($manager, $vente->getTotalBenefices(), $exercice, $vente->getDate(), $vente);
          foreach ($ecrituresDeVente as $ecr) {
            $ecrituresComptables[] = $ecr;
          }
          foreach ($ecrituresDeResultats as $ecr) {
            $ecrituresComptables[] = $ecr;
          }
        }
        
        //On fais le total général des entrées et le total des entrées selon le mode de paiement
        $totalEntrees = 0;
        $totalCaisse  = 0;
        $totalBanque  = 0;
        $totalAcompte = 0;
        $totalSM      = 0;
        foreach ($settlements as $value) {
          $mode = $value->getModepaiement();
          $dateReglement = $value->getDate();
          $montant = $value->getAmount();

          $reglements = $fonctions->ecriture_de_reglements_clients_dans_le_journal_comptable($manager, $mode, $montant, $exercice, $dateReglement, $value);
          foreach ($reglements as $ecr) {
            $ecrituresComptables[] = $ecr;
          }

          if($mode == 1)
            $totalCaisse = $totalCaisse + $montant;
          if($mode == 2)
            $totalBanque = $totalBanque + $montant;
          if($mode == 3)
            $totalAcompte = $totalAcompte + $montant;
          if($mode == 4)
            $totalSM = $totalSM + $montant;
        }
        $totalEntrees = $totalCaisse + $totalBanque + $totalSM;
        
        // Total des avoirs du jour
        $totalAvoirs = 0;
        $montantTva = 0;
        foreach ($avoirs as $avoir) {
          $totalAvoirs = $totalAvoirs + $avoir->getMontant();
          $resultat = 0;
          foreach ($avoir->getCommande()->getProduct() as $bene) {
            $resultat = $resultat + $bene->getBenefice();
          }
          $ecrituresDesAvoirs = $fonctions->ecriture_du_retour_de_marchandises_apres_une_vente($manager, $exercice, $avoir->getMontant(), $montantTva, $resultat, $avoir->getCommande());
          foreach ($ecrituresDesAvoirs as $ecr) {
            $ecrituresComptables[] = $ecr;
          }
        }

        // Total des dépenses du jour
        $totalDepenses = 0;
        foreach ($depensesDuJour as $depense) {
          $totalDepenses = $totalDepenses + $depense->getAmount();
          $mode          = $depense->getModePaiement();
          $ecrituresDesDepenses = $fonctions->ecritureDeDepensesDansLeJournalComptable($manager, $depense->getType()->getId(), $depense->getAmount(), $mode, $depense, $depense->getDescription(), $exercice);
          foreach ($ecrituresDesDepenses as $ecr) {
            $ecrituresComptables[] = $ecr;
          }
        }
        
        // Total des acomptes du jour
        $totalAcomptesClients      = 0;
        $totalAcomptesFournisseurs = 0;
        foreach ($acomptes as $acompte) {
          if(!empty($acompte->getCustomer())){
            $nom = $acompte->getCustomer()->getNom();
            $totalAcomptesClients = $totalAcomptesClients + $acompte->getMontant();
            $ecrAcpt = $fonctions->ecriture_des_avances_ou_des_creances($manager, $acompte->getMontant(), $exercice, new \DateTime(), $nom, true, "client");
            $ecrituresComptables[] = $ecrAcpt[0];
          }
          elseif(!empty($acompte->getProvider())){
            $nom = $acompte->getProvider()->getNom();
            $totalAcomptesFournisseurs = $totalAcomptesFournisseurs + $acompte->getMontant();
            $ecrAcpt = $fonctions->ecriture_des_avances_ou_des_creances($manager, $acompte->getMontant(), $exercice, new \DateTime(), $nom, true, "forunisseur");
            $ecrituresComptables[] = $ecrAcpt[0];
          }
        }

        // Total des acomptes du jour
        $totalRetraitAcomptesClients      = 0;
        foreach ($retraitsAcompte as $retrait) {
          $nom = $retrait->getCustomer()->getNom();
          $totalRetraitAcomptesClients = $totalRetraitAcomptesClients + $retrait->getMontant();
          $ecrAcpt = $fonctions->ecriture_de_retrait_acompte_client_dans_le_journal_comptable($manager, $mode, $retrait->getMontant(), $exercice, new \DateTime(), $retrait, $nom);
          $ecrituresComptables[] = $ecrAcpt[0];
        }

        // dd($retraitsAcompte);

        // Total des créances du jour, des bénéfices et des remises
        $totalArrieres  = 0;
        $totalDesAchats = 0;
        foreach ($achatsDUnJour as $achat) {
          $tva             = $achat->getTva();
          $dateCommande    = $achat->getDate();
          $transport       = $achat->getTransport();
          $dedouanement    = $achat->getDedouanement();
          $currency_cost   = $achat->getCurrencyCost();
          $forwarding_cost = $achat->getForwardingCost();
          $additional_fees = $achat->getAdditionalFees();
          $netAPayer       = $achat->getNetAPayer();
          $totalDesAchats  = $totalDesAchats + $netAPayer;

          $ecrituresDesAchats = $fonctions->ecritureDAchatDansLeJournalComptable($manager, $netAPayer, $tva, $exercice, $dateCommande, $achat);
          $ecrituresDesChargesAchats = $fonctions->ecritureDesChargesDUnAchatDansLeJournalComptable($manager, $transport, $dedouanement, $currency_cost, $forwarding_cost, $additional_fees, $achat, $exercice);
          $$totalArrieres  = $totalArrieres + $achat->getNetAPayer() - $achat->getTotalSettlments();
          foreach ($ecrituresDesAchats as $ecr) {
            $ecrituresComptables[] = $ecr;
          }
          foreach ($ecrituresDesChargesAchats as $ecr) {
            $ecrituresComptables[] = $ecr;
          }
        }

        $totalReglementsFournisseurs = 0;
        foreach ($reglementsFournisseurs as $value) {
          $mode = $value->getModepaiement();
          $dateReglement = $value->getDate();
          $montant = $value->getAmount();
          $totalReglementsFournisseurs = $totalReglementsFournisseurs + $montant;

          $reglements = $fonctions->ecritureDeReglementsFournisseursDansLeJournalComptable($manager, $mode, $montant, $exercice, $dateReglement, $value);
          foreach ($reglements as $ecr) {
            $ecrituresComptables[] = $ecr;
          }
        }
        
        $cloture = new Cloture();
        $cloture->setDate(new \DateTime($date));
        $cloture->setExercice($exercice);
        $cloture->setTotalAchats($totalDesAchats);
        $cloture->setTotalAcompteClients($totalAcomptesClients);
        $cloture->setTotalAcompteFournisseurs($totalAcomptesFournisseurs);
        $cloture->setTotalEntrees($totalEntrees);
        $cloture->setTotalArrieres($totalArrieres);
        $cloture->setTotalBenefices($totalBenefices);
        $cloture->setTotalCreances($totalCreances);
        $cloture->setTotalDepenses($totalDepenses);
        $cloture->setTotalEntreesBanque($totalBanque);
        $cloture->setTotalEntreesCaisse($totalCaisse);
        $cloture->setTotalEntreesServicesMoney($totalSM);
        $cloture->setTotalRegelementAcompte($totalAcompte);
        $cloture->setTotalReglementFournisseur($totalReglementsFournisseurs);
        $cloture->setTotalRemises($totalRemises);
        $cloture->setTotalVentes($totalDesVentes);
        $cloture->setTotalAvoirs($totalAvoirs);
        $cloture->setCreatedAt(new \DateTime());
        $cloture->setCreatedBy($this->getUser());
        $manager->persist($cloture);
        // Obtient une liste de colonnes
        foreach ($ecrituresComptables as $key => $row) {
          $dateEJ[$key]  = $row->getDate()->format("Y-m-d");
          $montantEJ[$key] = $row->getMontant();
        }
        array_multisort($dateEJ, SORT_ASC, $montantEJ, SORT_DESC, $ecrituresComptables);

        $cpt = 1;
        foreach ($ecrituresComptables as $value) {
          $reference = $fonctions->generateReferenceEcriture($manager, $cpt);
          $value->setNumero($reference);
          $cpt++;
        }
        // dd($cloture, $ecrituresComptables);
  
        try{
          $manager->flush();
          $this->addFlash('success', 'Activités du <strong>'.$dateFr.'</strong> clôturées avec succèss. Vous ne pouvez plus apporter de changement aux activités de ce jour.');
        } 
        catch(\Exception $e){
          $this->addFlash('danger', $e->getMessage());
          return $this->redirectToRoute('compte_journalier');
        }
  
      }
      else{
        $this->addFlash('danger', 'Impossible de continuer. Il faut cliquer sur le bon lien');
      }
      return $this->redirectToRoute('compte_journalier');
    }


    /**
     * @Route("/impression-des-dettes", name="print_dettes")
     */
    public function impression_des_dettes(EntityManagerInterface $manager)
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
        $html = $this->renderView('Accounting/impression-dettes.html.twig', [
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
    public function impression_des_creances(EntityManagerInterface $manager)
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
        $html = $this->renderView('Accounting/impression-creances.html.twig', [
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

    /**
     * @Route("/impression-des-ventes-du-jour/{date}", name="impression_des_ventes_du_jour")
     */
    public function impression_des_ventes_du_jour($date, EntityManagerInterface $manager, CheckConnectedUser $checker)
    {
      if($checker->getAccess() == true)
        return $this->redirectToRoute('login');

      $ventes = $manager->getRepository(CustomerCommande::class)->venteDuJour($date);

      $dateVente = $this->dateEnFrancais($date);
      // dd($ventes);
      if(empty($ventes)){
        $this->addFlash('danger', 'La date saisie n\'est pas correcte.');
        return $this->redirectToRoute('compte_journalier');
      }
      
      // Configure Dompdf according to your needs
      $pdfOptions = new Options();
      $pdfOptions->set('defaultFont', 'Arial');
      
      // Instantiate Dompdf with our options
      $dompdf = new Dompdf($pdfOptions);
      
      // Retrieve the HTML generated in our twig file
      $html = $this->renderView('Accounting/impression-ventes-du-jour.html.twig', [
          'ventes'  => $ventes,
          'dateVente'  => $dateVente,
          'current' => 'accounting',
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
      $dompdf->stream("ventes du $dateVente - Smart Market.pdf", [
          "Attachment" => false
      ]);
    }

    /**
   * @Route("/acomptes-clients", name="acomptes_clients")
   * @IsGranted("ROLE_ADMIN")
   */
  public function acomptes_clients(EntityManagerInterface $manager)
  {
    $customers = $manager->getRepository(Customer::class)->acomptes_clients();
    return $this->render('Accounting/acomptes-clients.html.twig', [
      'current'   => 'sells',
      'customers' => $customers
    ]);
  }
}
