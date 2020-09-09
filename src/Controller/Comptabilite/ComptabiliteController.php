<?php

namespace App\Controller\Comptabilite;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Product;
use App\Entity\Customer;
use App\Entity\Provider;
use App\Entity\ComptaClasse;
use App\Entity\ComptaCompte;
use App\Entity\Informations;
use App\Entity\ComptaEcriture;
use App\Entity\ComptaExercice;
use App\Form\ComptaCompteType;
use App\Service\CheckConnectedUser;
use App\Entity\ComptaCompteExercice;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\FonctionsComptabiliteController;
use App\Entity\Activite;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/admin/comptabilite")
 * @Security("has_role('ROLE_SUPER_ADMIN')")
 */
class ComptabiliteController extends AbstractController
{
    /**
     * @Route("/etat-de-l-entreprise", name="etat_entreprise")
     */
    public function etat_entreprise(EntityManagerInterface $manager, CheckConnectedUser $checker, FonctionsComptabiliteController $fonctions)
    {
      // $jourDuMois = (new \DateTime())->format("d");
      // dd((int) $jourDuMois);
        if($checker->getAccess() == true){
          return $this->redirectToRoute('login');
        }

        $acomptesClients      = 0;
        $creancesClients      = 0;
        $acomptesFournisseurs = 0;
        $dettesFournisseurs   = 0;
        $stockMarchandises    = 0;
        $products = $manager->getRepository(Product::class)->findAll();
        foreach ($products as $item) {
          if ($item->getIsDeleted() == 0) {
            $stockMarchandises = $stockMarchandises + $item->getTotalStock(2) * $item->getAveragePurchasePrice();//178 493 932 - 181 338 865
          }
        }

        $customers = $manager->getRepository(Customer::class)->findAll();
        foreach ($customers as $item) {
          if ($item->getIsDeleted() == 0) {
            $totalCommandes = $item->getMontantTotalCommandeNonSoldees();
            $totalReglements = $item->getMontantTotalReglementCommandeNonSoldees();
            $creance = $totalCommandes - $totalReglements;
            $acomptesClients = $acomptesClients + $item->getAcompte();
            $creancesClients = $creancesClients + $creance;
          }
        }

        $providers = $manager->getRepository(Provider::class)->findAll();
        $com = [];
        foreach ($providers as $item) {
          if ($item->getIsDeleted() == 0) {
            $totalCommandes = $item->getMontantTotalCommandeNonSoldees();
            $totalReglements = $item->getMontantTotalReglementCommandeNonSoldees();
            $dette = $totalCommandes - $totalReglements;
            $com[] = [$totalCommandes, $totalReglements, $dette];
            $acomptesFournisseurs = $acomptesFournisseurs + $item->getAcompte();
            $dettesFournisseurs = $dettesFournisseurs + $dette;
          }
        }
        // $resultat = $fonctions->determinationDuResultatDeLExercice($exercice);
        // dd($com);

        $exercices = $manager->getRepository(ComptaExercice::class)->findAll();
        $exercices = array_reverse($exercices);
        return $this->render('Comptabilite/index.html.twig', [ 
          'current'              => 'accounting',
          'exercices'            => $exercices,
          'acomptesClients'      => $acomptesClients,
          'creancesClients'      => $creancesClients,
          'acomptesFournisseurs' => $acomptesFournisseurs,
          'dettesFournisseurs'   => $dettesFournisseurs,
          'stockMarchandises'    => $stockMarchandises,
        ]);
    }


    /**
     * @Route("/comptes-du-bilan-et-de-resultats", name="gestion_comptes")
     */
    public function gestion_des_comptes_du_bilan_et_de_resultats(EntityManagerInterface $manager, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $comptes = $manager->getRepository(ComptaCompte::class)->findAll();

        return $this->render('Comptabilite/gestion-des-comptes.html.twig', [
          'current' => 'accounting',
          'comptes' => $comptes,
        ]);
    }


    /**
     * @Route("/add", name="add_compte")
     */
    public function add(Request $request, EntityManagerInterface $manager)
    {
      $compte = new ComptaCompte();
      $form = $this->createForm(ComptaCompteType::class, $compte);
      $form->handleRequest($request);
      if($form->isSubmitted() && $form->isValid())
      {
        $compte->setCreatedBy($this->getUser());
        $manager->persist($compte);
        try{
          $manager->flush();
          $this->addFlash('success', 'Enregistrement du compte <strong>'.$compte->getNumero().' - '.$compte->getLabel().'</strong> réussie.');
        } 
        catch(\Exception $e){
          $this->addFlash('danger', $e->getMessage());
        } 
        return $this->redirectToRoute('gestion_comptes');
      }
        
      return $this->render('Comptabilite/compte-add.html.twig', [
        'current' => 'accounting',
        'form'    => $form->createView()
      ]);
    }

    /**
     * @Route("/edit/{id}", name="edit_compte")
     */
    public function edit(Request $request, EntityManagerInterface $manager, ComptaCompte $compte)
    {
      $form = $this->createForm(ComptaCompteType::class, $compte);
      $form->handleRequest($request);
      if($form->isSubmitted() && $form->isValid())
      {
        $compte->setUpdatedAt(new \DateTime());
        $compte->setUpdatedBy($this->getUser());
        try{
          $manager->flush();
          $this->addFlash('success', 'Mise à jour du compte <strong>'.$compte->getNumero().' - '.$compte->getLabel().'</strong> réussie.');
        } 
        catch(\Exception $e){
          $this->addFlash('danger', $e->getMessage());
        } 
        return $this->redirectToRoute('gestion_comptes');
      }
        
      return $this->render('Comptabilite/compte-edit.html.twig', [
        'current' => 'accounting',
        'compte'  => $compte,
        'form'    => $form->createView()
      ]);
    }


    /**
     * @Route("/information-exercice", name="info_exercice")
     */
    public function exercices(Request $request, EntityManagerInterface $manager)
    {
      $exercice = $manager->getRepository(ComptaExercice::class)->findAll();
      return $this->render('Comptabilite/info-exercice.html.twig', [
        'current'  => 'accounting',
        'exercice' => $exercice
      ]);
    }


    /**
     * @Route("/enregistrer-bilan-d-ouverture", name="enregistrer_bilan_ouverture")
     */
    public function enregistrer_bilan_ouverture(Request $request, EntityManagerInterface $manager)
    {
      $comptesActifs  = $manager->getRepository(ComptaCompte::class)->comptesDuBilanOuDuResultat("bilan", "actif");
      $comptesPassifs = $manager->getRepository(ComptaCompte::class)->comptesDuBilanOuDuResultat("bilan", "passif");
      $classesActifs  = $manager->getRepository(ComptaClasse::class)->classesDuBilanOuDuResultat("bilan", "actif");
      $classesPassifs = $manager->getRepository(ComptaClasse::class)->classesDuBilanOuDuResultat("bilan", "passif");
      // $date = (new \DateTime())->format("Y-m-d");
      // $tab = [$date, date('01-m-Y', strtotime($date)), date('t-m-Y', strtotime($date))];
      // dump($tab);
      if($request->isMethod('post'))
      {
        $token = $request->get('_csrf_token');
        if($this->isCsrfTokenValid('bilan_ouverture', $token))
        {
          $data           = $request->request->all();
          $dateDebut      = new \DateTime($data["date"]);
          $dateFin        = new \DateTime(date('t-m-Y', strtotime($data["date"])));
          $totalActif     = (int) $data["totalActif"];
          $totalPassif    = (int) $data["totalPassif"];
          $comptesActifs  = $data["comptesActifs"];
          $comptesPassifs = $data["comptesPassifs"];
          $labelExercice  = $this->dateEnFrancais($dateDebut, false);
          // dd($labelExercice);
          if($totalActif !== $totalPassif){
            $this->addFlash('danger', "Impossible de continuer. Total actif différent de total passif");
            return $this->redirectToRoute('enregistrer_bilan_ouverture');    
          }

          // On commence par enregistrer le tout premier exercice de l'entreprise
          $exercice = new ComptaExercice();
          $exercice->setDateDebut($dateDebut);
          $exercice->setDateFin($dateFin);
          $exercice->setLabel($labelExercice);
          $exercice->setMois($dateDebut->format("m-Y"));
          $exercice->setCreatedBy($this->getUser());
          $manager->persist($exercice);

          // Vient maintenant le moment d'enregistrer les comptes du bilan et ceux du résultat, sachant que le montant initial des comptes du résultat est nul.
          // On commence par les comptes de l'actif du bilan
          foreach ($comptesActifs as $key => $value) {
            $compte = $manager->getRepository(ComptaCompte::class)->findOneByNumero($key);
            $compteExercice = new ComptaCompteExercice();
            $compteExercice->setExercice($exercice);
            $compteExercice->setCompte($compte);
            $compteExercice->setMontantInitial($value);
            $compteExercice->setMontantFinal($value);
            $compteExercice->setCreatedBy($this->getUser());
            $manager->persist($compteExercice);
            // dd($compteExercice);
          }

          // Ensuite, les comptes du passif du bilan
          foreach ($comptesPassifs as $key => $value) {
            $compte = $manager->getRepository(ComptaCompte::class)->findOneByNumero($key);
            $compteExercice = new ComptaCompteExercice();
            $compteExercice->setExercice($exercice);
            $compteExercice->setCompte($compte);
            $compteExercice->setMontantInitial($value);
            $compteExercice->setMontantFinal($value);
            $compteExercice->setCreatedBy($this->getUser());
            $manager->persist($compteExercice);
            // dd($compteExercice);
          }

          // Et enfin, les comptes du résutat
          $comptesResutats  = $manager->getRepository(ComptaCompte::class)->comptesDuBilanOuDuResultat("resultat");
          foreach ($comptesResutats as $key => $value) {
            $compteExercice = new ComptaCompteExercice();
            $compteExercice->setExercice($exercice);
            $compteExercice->setCompte($value);
            $compteExercice->setMontantInitial(0);
            $compteExercice->setMontantFinal(0);
            $compteExercice->setCreatedBy($this->getUser());
            $manager->persist($compteExercice);
            // dd($compteExercice);
          }

          try{
            $manager->flush();
            $this->addFlash('success', 'Enregistrement du bilan d\'ouverture réussi.');
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          } 
          return $this->redirectToRoute('etat_entreprise');
        }
      }
      return $this->render('Comptabilite/enregistrer-bilan-ouverture.html.twig', [
        'current'        => 'accounting',
        'comptesActifs'  => $comptesActifs,
        'comptesPassifs' => $comptesPassifs,
        'classesActifs'  => $classesActifs,
        'classesPassifs' => $classesPassifs,
      ]);
    }

    /**
     * @Route("/ajuster-bilan-d-exercice/{id}", name="ajuster_bilan")
     * @param ComptaExercice $exercice
     */
    public function ajuster_bilan(Request $request, EntityManagerInterface $manager, ComptaExercice $exercice, int $id)
    {
      $comptesActifs  = $manager->getRepository(ComptaCompte::class)->comptesDuBilanOuDuResultat("bilan", "actif");
      $comptesPassifs = $manager->getRepository(ComptaCompte::class)->comptesDuBilanOuDuResultat("bilan", "passif");
      $classesActifs  = $manager->getRepository(ComptaClasse::class)->classesDuBilanOuDuResultat("bilan", "actif");
      $classesPassifs = $manager->getRepository(ComptaClasse::class)->classesDuBilanOuDuResultat("bilan", "passif");
      $comptesExercice = $manager->getRepository(ComptaCompteExercice::class)->findByExercice($id);
      // $date = (new \DateTime())->format("Y-m-d");
      // $tab = [$date, date('01-m-Y', strtotime($date)), date('t-m-Y', strtotime($date))];
      // dump($comptesExercice);
      if($request->isMethod('post'))
      {
        $token = $request->get('_csrf_token');
        if($this->isCsrfTokenValid('ajuster_bilan', $token))
        {
          $data           = $request->request->all();
          $totalActif     = (int) $data["totalActif"];
          $totalPassif    = (int) $data["totalPassif"];
          $comptesActifs  = $data["comptesActifs"];
          $comptesPassifs = $data["comptesPassifs"];
          // dd($labelExercice);
          if($totalActif !== $totalPassif){
            $this->addFlash('danger', "Impossible de continuer. Total actif différent de total passif");
            return $this->redirectToRoute('enregistrer_bilan_ouverture');    
          }


          // Vient maintenant le moment d'enregistrer les comptes du bilan et ceux du résultat, sachant que le montant initial des comptes du résultat est nul.
          // On commence par les comptes de l'actif du bilan
          foreach ($comptesActifs as $key => $value) {
            foreach ($comptesExercice as $compte) {
              if($key == $compte->getCompte()->getNumero()){
                $compte->setMontantFinal($value);
                $compte->setUpdatedBy($this->getUser());
                $compte->setUpdatedAt(new \DateTime());
              }
            }
            // dd($compteExercice);
          }

          // Ensuite, les comptes du passif du bilan
          foreach ($comptesPassifs as $key => $value) {
            foreach ($comptesExercice as $compte) {
              if($key == $compte->getCompte()->getNumero()){
                $compte->setMontantFinal($value);
                $compte->setUpdatedBy($this->getUser());
                $compte->setUpdatedAt(new \DateTime());
              }
            }
            // dd($exercice);
          }

          $activite = new Activite();
          $activite->setTitre("Ajustement bilan");
          $activite->setDescription("Ajustement du bilan de ".$exercice->getLabel());
          $activite->setDate(new \DateTime());
          $activite->setUser($this->getUser());
          $manager->persist($activite);

          try{
            $manager->flush();
            $this->addFlash('success', 'Ajustement du bilan de <strong>'.$exercice->getLabel().'</strong> réussi.');
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          } 
          return $this->redirectToRoute('etat_entreprise');
        }
      }
      
      return $this->render('Comptabilite/ajuster-bilan.html.twig', [
        'current'         => 'accounting',
        'exercice'        => $exercice,
        'comptesActifs'   => $comptesActifs,
        'comptesPassifs'  => $comptesPassifs,
        'classesActifs'   => $classesActifs,
        'classesPassifs'  => $classesPassifs,
        'comptesExercice' => $comptesExercice,
      ]);
    }

    /**
     * @Route("/balance-d-exercice/{id}", name="balance_d_un_exercice")
     * @param ComptaExercice $exercice
     */
    public function balance_d_un_exercice(Request $request, EntityManagerInterface $manager, ComptaExercice $exercice, int $id)
    {
      $comptes  = $manager->getRepository(ComptaCompte::class)->comptesDuBilanOuDuResultat("bilan");
      $ecritures = [];
      if($request->isMethod('post'))
      {
        $token = $request->get('token');
        if($this->isCsrfTokenValid('balance_compte', $token))
        {
          $data = $request->request->all();
          $compteId = $data["compte"];
          $ecritures  = $manager->getRepository(ComptaEcriture::class)->ecrituresDUnCompteDeLExercice($id, $compteId);
          // dd($ecritures);
        }
      }
      
      return $this->render('Comptabilite/balance.html.twig', [
        'current'  => 'accounting',
        'ecritures' => $ecritures,
        'exercice' => $exercice,
        'comptes'  => $comptes,
      ]);
    }

    /**
     * @Route("/bilan-initial-d-un-exercice/{id}", name="bilan_initial")
     */
    public function bilan_initial_d_un_exercice(ComptaExercice $exercice, int $id, EntityManagerInterface $manager)
    {
      $comptesActifs  = $manager->getRepository(ComptaCompteExercice::class)->comptesDuBilanOuDuResultat("bilan", "actif", $id);
      $comptesPassifs = $manager->getRepository(ComptaCompteExercice::class)->comptesDuBilanOuDuResultat("bilan", "passif", $id);
      $classesActifs  = $manager->getRepository(ComptaClasse::class)->classesDuBilanOuDuResultat("bilan", "actif");
      $classesPassifs = $manager->getRepository(ComptaClasse::class)->classesDuBilanOuDuResultat("bilan", "passif");

      return $this->render('Comptabilite/bilan-initial.html.twig', [
        'current'        => 'accounting',
        'exercice'        => $exercice,
        'comptesActifs'  => $comptesActifs,
        'comptesPassifs' => $comptesPassifs,
        'classesActifs'  => $classesActifs,
        'classesPassifs' => $classesPassifs,
      ]);
    }

    /**
     * @Route("/bilan-final-d-un-exercice/{id}", name="bilan_final")
     */
    public function bilan_final_d_un_exercice(ComptaExercice $exercice, int $id, EntityManagerInterface $manager)
    {
      $comptesActifs  = $manager->getRepository(ComptaCompteExercice::class)->comptesDuBilanOuDuResultat("bilan", "actif", $id);
      $comptesPassifs = $manager->getRepository(ComptaCompteExercice::class)->comptesDuBilanOuDuResultat("bilan", "passif", $id);
      $classesActifs  = $manager->getRepository(ComptaClasse::class)->classesDuBilanOuDuResultat("bilan", "actif");
      $classesPassifs = $manager->getRepository(ComptaClasse::class)->classesDuBilanOuDuResultat("bilan", "passif");
      $sommeCharges   = $manager->getRepository(ComptaCompteExercice::class)->sommeDesComptesResultatExercice(1, $id);
      $sommeProduits  = $manager->getRepository(ComptaCompteExercice::class)->sommeDesComptesResultatExercice(2, $id);
      // dump($sommeCharges);

      return $this->render('Comptabilite/bilan-final.html.twig', [
        'current'        => 'accounting',
        'exercice'        => $exercice,
        'comptesActifs'  => $comptesActifs,
        'comptesPassifs' => $comptesPassifs,
        'classesActifs'  => $classesActifs,
        'classesPassifs' => $classesPassifs,
      ]);
    }

    /**
     * @Route("/resultat-d-un-exercice/{id}", name="resultat_d_un_exercice")
     */
    public function resultat_d_un_exercice(ComptaExercice $exercice, int $id, EntityManagerInterface $manager)
    {
      $comptesCharges  = $manager->getRepository(ComptaCompteExercice::class)->comptesDuBilanOuDuResultat("resultat", "charges", $id);
      $comptesProduits = $manager->getRepository(ComptaCompteExercice::class)->comptesDuBilanOuDuResultat("resultat", "produits", $id);
      $classesCharges  = $manager->getRepository(ComptaClasse::class)->classesDuBilanOuDuResultat("resultat", "charges");
      $classesProduits = $manager->getRepository(ComptaClasse::class)->classesDuBilanOuDuResultat("resultat", "produits");

      return $this->render('Comptabilite/resultat-exercice.html.twig', [
        'current'         => 'accounting',
        'exercice'        => $exercice,
        'comptesCharges'  => $comptesCharges,
        'comptesProduits' => $comptesProduits,
        'classesCharges'  => $classesCharges,
        'classesProduits' => $classesProduits,
      ]);
    }

    /**
     * @Route("/journal-d-un-exercice/{id}", name="journal")
     */
    public function journal_d_un_exercice(ComptaExercice $exercice, int $id, EntityManagerInterface $manager)
    {
      $ecritures = $manager->getRepository(ComptaEcriture::class)->ecrituresDeLExercice($exercice);
      return $this->render('Comptabilite/journal.html.twig', [
        'current'   => 'accounting',
        'exercice'  => $exercice,
        'ecritures' => $ecritures,
      ]);
    }


    /**
     * @Route("/enregistrer-une-ecriture-dans-le-journal-d-un-exercice/{id}", name="ecrire_dans_journal")
     */
    public function ecrire_dans_journal_d_un_exercice(Request $request, ComptaExercice $exercice, int $id, EntityManagerInterface $manager, FonctionsComptabiliteController $fonctions)
    {
      if($exercice->getAcheve() == 1){
        $this->addFlash('danger', "Impossible d'écrire dans le journal. L'exercice <strong>".$exercice->getLabel()."</strong> est déjà achevé.");
        return $this->redirectToRoute('journal', ["id" => $id]);    
      }

      if($request->isMethod('post'))
      {
        $token = $request->get('_csrf_token');
        if($this->isCsrfTokenValid('ecriture_comptable', $token))
        {
          $data      = $request->request->all();
          $date      = new \DateTime($data["date"]);
          $reference = $data["reference"];
          $label     = $data["label"];
          $debitId   = (int) $data["debit"];
          $creditId  = (int) $data["credit"];
          $montant   = (int) $data["montant"];
          $tva       = (int) $data["tva"];

          // On va vérifier que l'opération se passe entre deux comptes différents
          if($debitId == $creditId){
            $this->addFlash('danger', "Opération impossible. Le mouvement se passe dans le même compte.");
            return $this->redirectToRoute('ecrire_dans_journal', ["id" => $id]);      
          }
          else{
            $debit  = $manager->getRepository(ComptaCompteExercice::class)->find($debitId);
            $credit = $manager->getRepository(ComptaCompteExercice::class)->find($creditId);
          }

          
          $remarque = isset($data["remarque"]) ? $data["remarque"] : null;
          $ecriture = new ComptaEcriture();
          $ecriture->setExercice($exercice);
          $ecriture->setNumero($reference);
          $ecriture->setDate($date);
          $ecriture->setLabel($label);
          $ecriture->setDebit($debit);
          $ecriture->setCredit($credit);
          $ecriture->setTva($tva);
          $ecriture->setMontant($montant);
          $ecriture->setRemarque($remarque);
          $ecriture->setIsEditable(true);
          $ecriture->setCreatedBy($this->getUser());
          $manager->persist($ecriture);

          /**
           * Ici, on va maintenant débiter et créditer les comptes qu'il faut. Quatre cas peuvent se présenter.
           *    1 - Le compte à débiter est un compte de l'actif et le compte à créditer est aussi un compte de l'actif
           *    2 - Le compte à débiter est un compte de l'actif mais le compte à créditer est un compte du passif
           *    3 - Le compte à débiter est un compte du passif et le compte à créditer est aussi un compte du passif
           *    4 - Le compte à débiter est un compte du passif mais le compte à créditer est aussi un compte de l'actif
           */
          $typeCompteADebiter = $debit->getCompte()->getClasse()->getType()->getId();
          $typeCompteACrediter = $credit->getCompte()->getClasse()->getType()->getId();
          if($typeCompteADebiter === $typeCompteACrediter and $typeCompteACrediter === 1){
            $nouveauMontantCompteADebiter  = $debit->getMontantFinal() + $montant;
            $nouveauMontantCompteACrediter = $credit->getMontantFinal() - $montant;
          }
          elseif($typeCompteADebiter === $typeCompteACrediter and $typeCompteACrediter === 2){
            $nouveauMontantCompteADebiter  = $debit->getMontantFinal() - $montant;
            $nouveauMontantCompteACrediter = $credit->getMontantFinal() + $montant;
          }

          if($typeCompteADebiter !== $typeCompteACrediter and $typeCompteADebiter === 1){
            $nouveauMontantCompteADebiter  = $debit->getMontantFinal() + $montant;
            $nouveauMontantCompteACrediter = $credit->getMontantFinal() + $montant;
          }
          elseif($typeCompteADebiter !== $typeCompteACrediter and $typeCompteADebiter === 2){
            $nouveauMontantCompteADebiter  = $debit->getMontantFinal() - $montant;
            $nouveauMontantCompteACrediter = $credit->getMontantFinal() - $montant;
          }

          $debit->setMontantFinal($nouveauMontantCompteADebiter);
          $credit->setMontantFinal($nouveauMontantCompteACrediter);
          if($nouveauMontantCompteACrediter < 0 or $nouveauMontantCompteADebiter < 0){
            $this->addFlash('danger', "Opération impossible! La somme indiquée (<strong>".number_format($montant, 0, ',', ' ')." F</strong>) ne peut être débitée/créditée des comptes sélectionnés.");
            return $this->redirectToRoute('ecrire_dans_journal', ["id" => $id]);    

          }
          // dd($debit->getMontantFinal(), $credit->getMontantFinal());
          try{
            $manager->flush();
            $this->addFlash('success', "Enregistrement de l'écriture N°<strong>".$reference."</strong> réussi.");
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          } 
          return $this->redirectToRoute('journal', ["id" => $id]);    
        }
      }
      // $derniereEcriture = $manager->getRepository(ComptaEcriture::class)->last_saved();
      $reference = $fonctions->generateReferenceEcriture($manager);
      $comptes  = $manager->getRepository(ComptaCompteExercice::class)->comptesDuBilanOuDuResultat("tous", null, $id);
      return $this->render('Comptabilite/ecrire-dans-journal.html.twig', [
        'current'  => 'accounting',
        'exercice' => $exercice,
        'reference' => $reference,
        'comptes'   => $comptes,
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
        $date = $date instanceof \DateTime ? $date->format("Y-m-d") : $date;
        $date = utf8_encode(strftime("%A $format_jour %B %Y", strtotime($date)));
        $dateEnFrancais = ucwords($date);
      }
      else{
        $date = $date instanceof \DateTime ? $date->format("Y-m-d") : $date;
        $mois = utf8_encode(strftime("$format_jour %B %Y", strtotime($date)));
        $dateEnFrancais = ucfirst(substr($mois, 3));
      }

      return $dateEnFrancais;
    }

}
