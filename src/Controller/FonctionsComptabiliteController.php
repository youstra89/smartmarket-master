<?php

namespace App\Controller;

use App\Entity\Settlement;
use App\Entity\ComptaEcriture;
use App\Entity\ComptaExercice;
use App\Entity\CustomerCommande;
use App\Entity\ComptaCompteExercice;
use App\Entity\Depense;
use App\Entity\ProviderCommande;
use App\Entity\ProviderSettlement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class FonctionsComptabiliteController extends AbstractController
{
    // Cette fonction permet de mouvementer les comptes lors d'une achat de marchandises
    public function ecritureDeVenteDansLeJournalComptable(EntityManagerInterface $manager, int $totalMarchandises, int $resultat, int $tva, object $exercice, \DateTime $date, CustomerCommande $vente)
    {
      $exerciceId = $exercice->getId();
      $montantTva = ($totalMarchandises * $tva) / 100;

      // 1 - On commence par créditer le compte marchandise
      $compteMarchandise = $manager->getRepository(ComptaCompteExercice::class)->findCompte(11, $exerciceId);
      $compteMarchandise->setMontantFinal($compteMarchandise->getMontantFinal() - $totalMarchandises);

      // 2 - On débite enfin le compte client
      $compteClient = $manager->getRepository(ComptaCompteExercice::class)->findCompte(9, $exerciceId);
      $compteClient->setMontantFinal($compteClient->getMontantFinal() + $totalMarchandises);

      // 3 - On va maintenant créditer le compte Vente de marchandises
      $compteVenteMarchandises = $manager->getRepository(ComptaCompteExercice::class)->findCompte(25, $exerciceId);
      $compteVenteMarchandises->setMontantFinal($compteVenteMarchandises->getMontantFinal() + $resultat);

      // 4 - On met à jour le compte résultat
      // $this->determinationDuResultatDeLExercice($manager, $exercice);
      
      // 5 - En cinquième et dernière étape, on écrit dans le journal
      $derniereEcriture = $manager->getRepository(ComptaEcriture::class)->last_saved();
      $reference = $this->generateReferenceEcriture($derniereEcriture);

      $label           = "Vente de marchandises";
      $tva             = 0;
      $montant         = $totalMarchandises;
      $remarque        = null;
      $compteADebiter  = $compteClient;
      $compteAcrediter = $compteMarchandise;
      $ecriture_liee_a = $vente;
      $ecriture = $this->genererNouvelleEcritureDuJournal($exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque, $ecriture_liee_a);
      $manager->persist($ecriture);


      if($montantTva != 0){
        // 1 - On créditer le compte TVA du montant de la TVA si et seulement si la TVA n'est pas nulle
        $compteTVACollectee = $manager->getRepository(ComptaCompteExercice::class)->findCompte(17, $exerciceId);
        $compteTVACollectee->setMontantFinal($compteTVACollectee->getMontantFinal() + $montantTva);
  
        // 2 - On débiter enfin le compte client
        $compteClient->setMontantFinal($compteClient->getMontantFinal() + $montantTva);
  
        // 3 - Troisième et dernière étape, on écrit dans le journal
        $derniereEcriture = $manager->getRepository(ComptaEcriture::class)->last_saved();
        $reference        = $this->generateReferenceEcriture($derniereEcriture, 2);
        $label            = "TVA collectée sur vente de marchandises";
        $tva              = 0;
        $montant          = $montantTva;
        $remarque         = null;
        $compteADebiter   = $compteClient;
        $compteAcrediter  = $compteTVACollectee;
        $ecriture = $this->genererNouvelleEcritureDuJournal($exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque);
        $manager->persist($ecriture);
      }

      try{
        $manager->flush();
      } 
      catch(\Exception $e){
        $this->addFlash('danger', $e->getMessage());
      }
    }


    public function ecritureDuResultatDeVenteJournalComptable(EntityManagerInterface $manager, int $resultat, object $exercice, \DateTime $date, CustomerCommande $vente)
    {
      $exerciceId = $exercice->getId();
      $compteResultat = $manager->getRepository(ComptaCompteExercice::class)->findCompte(7, $exerciceId);
      $compteResultat->setMontantFinal($compteResultat->getMontantFinal() + $resultat);
      $compteClient = $manager->getRepository(ComptaCompteExercice::class)->findCompte(9, $exerciceId);
      $compteClient->setMontantFinal($compteClient->getMontantFinal() + $resultat);

      
      // 5 - En cinquième et dernière étape, on écrit dans le journal
      $derniereEcriture = $manager->getRepository(ComptaEcriture::class)->last_saved();
      $reference = $this->generateReferenceEcriture($derniereEcriture);

      $label           = $resultat > 0 ? "Bénéfice sur vente de marchandises" : "Perte sur vente de marchandises";
      $tva             = 0;
      $montant         = $resultat;
      $remarque        = null;
      $compteADebiter  = $compteClient;
      $compteAcrediter = $compteResultat;
      $ecriture_liee_a = $vente;
      $ecriture = $this->genererNouvelleEcritureDuJournal($exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque, $ecriture_liee_a);
      $manager->persist($ecriture);

      try{
        $manager->flush();
      } 
      catch(\Exception $e){
        $this->addFlash('danger', $e->getMessage());
      }
    }


    public function ecritureDeReglementsClientsDansLeJournalComptable(EntityManagerInterface $manager, int $mode, int $montant, object $exercice, \DateTime $date, Settlement $settlement)
    {
      $exerciceId = $exercice->getId();
      $referenceCommande = $settlement->getCommande()->getReference();

      // 1 - On commence par crediter le compte client
      $compteClient = $manager->getRepository(ComptaCompteExercice::class)->findCompte(9, $exerciceId);
      $compteClient->setMontantFinal($compteClient->getMontantFinal() - $montant);
      
      // 2 - On débiter ensuite soit le compte caisse, soit le compte banque
      if($mode == 1)
        $compteADebiter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(13, $exerciceId);
      elseif($mode == 2)
        $compteADebiter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(12, $exerciceId);

      $compteADebiter->setMontantFinal($compteADebiter->getMontantFinal() + $montant);

      // 3 - Et enfin la dernière étape, on écrit dans le journal
      $derniereEcriture = $manager->getRepository(ComptaEcriture::class)->last_saved();
      $reference = $this->generateReferenceEcriture($derniereEcriture);

      $label           = "Règlement de la commande N°$referenceCommande";
      $tva             = 0;
      $montant         = $montant;
      $remarque        = null;
      $compteADebiter  = $compteADebiter;
      $compteAcrediter = $compteClient;
      $ecriture_liee_a = $settlement;
      $ecriture = $this->genererNouvelleEcritureDuJournal($exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque, $ecriture_liee_a);
      $manager->persist($ecriture);

      try{
        $manager->flush();
      } 
      catch(\Exception $e){
        $this->addFlash('danger', $e->getMessage());
      }
    }


    public function ecritureDeModificationDeVente(EntityManagerInterface $manager, CustomerCommande $vente, int $ancienMontant)
    {
      $tva            = $vente->getTva();
      $nouveauMontant = $vente->getTotalAmount();
      $referenceVente = $vente->getReference();
      $exercice       = $vente->getExercice();
      $exerciceId     = $exercice->getId();
      $montant        = 0;
      $option         = "";

      /**
       * Si le nouveau montant de le vente est supérieur à l'ancien, alors il y a eu augmentation de la vente.
       * Dans ce cas, on va débiter le compte marchanise de la différence entre $nouveauMontant et $ancienMontant et créditer le compte client
       */
      if($nouveauMontant > $ancienMontant)
      {
        $montant = $nouveauMontant - $ancienMontant;
        $option  = "augmentation";
        $derniereEcriture = $manager->getRepository(ComptaEcriture::class)->last_saved();
        $reference = $this->generateReferenceEcriture($derniereEcriture);

        // 1 - On commence par créditer le compte marchandise
        $compteMarchandise = $manager->getRepository(ComptaCompteExercice::class)->findCompte(11, $exerciceId);
        $compteMarchandise->setMontantFinal($compteMarchandise->getMontantFinal() - $montant);

        // 2 - On débite enfin le compte client
        $compteClient = $manager->getRepository(ComptaCompteExercice::class)->findCompte(9, $exerciceId);
        $compteClient->setMontantFinal($compteClient->getMontantFinal() + $montant);

        // 3 - On va maintenant créditer le compte Vente de marchandises
        $compteVenteMarchandises = $manager->getRepository(ComptaCompteExercice::class)->findCompte(25, $exerciceId);
        $compteVenteMarchandises->setMontantFinal($compteVenteMarchandises->getMontantFinal() + $montant);

        $label           = "Vente de marchandises (augmentation de la vente N°$referenceVente)";
        $tva             = 0;
        $date            = new \DateTime();
        $montant         = $montant;
        $remarque        = null;
        $compteADebiter  = $compteClient;
        $compteAcrediter = $compteMarchandise;
        $ecriture_liee_a = $vente;
        $ecriture = $this->genererNouvelleEcritureDuJournal($exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque, $ecriture_liee_a);
        $manager->persist($ecriture);
      }

      /**
       * Sinon, ce sera une diminution. Ce qui veut dire que le client à retourner certaines marchandises.
       * On débite alors le compte client et on crédite le compte marchandises.
       */
      if($ancienMontant > $nouveauMontant)
      {
        $montant = $ancienMontant - $nouveauMontant;
        $option  = "diminution";

        // 1 - On commence par débiter le compte marchandise
        $compteMarchandise = $manager->getRepository(ComptaCompteExercice::class)->findCompte(11, $exerciceId);
        $compteMarchandise->setMontantFinal($compteMarchandise->getMontantFinal() + $montant);

        // 2 - On crédite enfin le compte client
        $compteClient = $manager->getRepository(ComptaCompteExercice::class)->findCompte(9, $exerciceId);
        $compteClient->setMontantFinal($compteClient->getMontantFinal() - $montant);

        // 3 - On va maintenant créditer le compte Achat de marchandises
        $compteAchatMarchandises = $manager->getRepository(ComptaCompteExercice::class)->findCompte(25, $exerciceId);
        $compteAchatMarchandises->setMontantFinal($compteAchatMarchandises->getMontantFinal() + $montant);

        $label           = "Vente de marchandises (diminution de la vente N°$referenceVente)";
        $tva             = 0;
        $date            = new \DateTime();
        $montant         = $montant;
        $remarque        = null;
        $compteADebiter  = $compteMarchandise;
        $compteAcrediter = $compteClient;
        $ecriture_liee_a = $vente;
        $ecriture = $this->genererNouvelleEcritureDuJournal($exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque, $ecriture_liee_a);
        $manager->persist($ecriture);
      }

      if($tva != 0){
        $montant    = abs($montant);
        $montantTva = ($montant * $tva) / 100;
        $compteTVACollectee = $manager->getRepository(ComptaCompteExercice::class)->findCompte(17, $exerciceId);
        if($option == "augmentation"){
          $label = "TVA collectée sur vente de marchandises (augmentation de marchandises)";
          // En cas d'augmentation, on crédite le compte TVA collectée et on débite enfin le compte client
          $compteAcrediter = $compteTVACollectee->setMontantFinal($compteTVACollectee->getMontantFinal() + $tva);
          $compteADebiter  = $compteClient->setMontantFinal($compteClient->getMontantFinal() + $tva);
        }
        elseif ($option == "diminution") {
          $label = "Débit du compte TVA collectée (diminution de marchandises)";
          $compteADebiter  = $compteTVACollectee->setMontantFinal($compteTVACollectee->getMontantFinal() - $tva);
          $compteAcrediter = $compteClient->setMontantFinal($compteClient->getMontantFinal() - $tva);
        }
  
        // 3 - Troisième et dernière étape, on écrit dans le journal
        $derniereEcriture = $manager->getRepository(ComptaEcriture::class)->last_saved();
        $reference = $this->generateReferenceEcriture($derniereEcriture, 2);

        $label           = "Vente de marchandises (diminution de la vente N°$referenceVente)";
        $tva             = 0;
        $date            = new \DateTime();
        $montant         = $montant;
        $remarque        = null;
        $compteADebiter  = $compteMarchandise;
        $compteAcrediter = $compteClient;
        $ecriture_liee_a = $vente;
        $ecriture = $this->genererNouvelleEcritureDuJournal($exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque, $ecriture_liee_a);
        $manager->persist($ecriture);
      }

      try{
        $manager->flush();
      } 
      catch(\Exception $e){
        $this->addFlash('danger', $e->getMessage());
      }
    }


    public function ecritureDAchatDansLeJournalComptable(EntityManagerInterface $manager, int $totalMarchandises, int $tva, object $exercice, \DateTime $date, ProviderCommande $achat)
    {
      $exerciceId = $exercice->getId();
      $montantTva = ($totalMarchandises * $tva) / 100;

      // 1 - On commence par débiter le compte marchandise
      $compteMarchandise = $manager->getRepository(ComptaCompteExercice::class)->findCompte(11, $exerciceId);
      $compteMarchandise->setMontantFinal($compteMarchandise->getMontantFinal() + $totalMarchandises);

      // 2 - On crédite enfin le compte client
      $compteFournisseur = $manager->getRepository(ComptaCompteExercice::class)->findCompte(14, $exerciceId);
      $compteFournisseur->setMontantFinal($compteFournisseur->getMontantFinal() + $totalMarchandises);

      // // 3 - On va maintenant créditer le compte Achat de marchandises
      // $compteAchatMarchandises = $manager->getRepository(ComptaCompteExercice::class)->findCompte(18, $exerciceId);
      // $compteAchatMarchandises->setMontantFinal($compteAchatMarchandises->getMontantFinal() + $totalMarchandises);

      // 4 - Quatième et dernière étape, on écrit dans le journal
      $derniereEcriture = $manager->getRepository(ComptaEcriture::class)->last_saved();
      $reference = $this->generateReferenceEcriture($derniereEcriture);

      $label           = "Achat de marchandises";
      $tva             = 0;
      $montant         = $totalMarchandises;
      $remarque        = null;
      $compteADebiter  = $compteMarchandise;
      $compteAcrediter = $compteFournisseur;
      $ecriture_liee_a = $achat;
      $ecriture = $this->genererNouvelleEcritureDuJournal($exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque, $ecriture_liee_a);

      $manager->persist($ecriture);


      if($montantTva != 0){
        // 1 - On debite le compte TVA du montant de la TVA si et seulement si la TVA n'est pas nulle
        $compteTVADeductible = $manager->getRepository(ComptaCompteExercice::class)->findCompte(10, $exerciceId);
        $compteTVADeductible->setMontantFinal($compteTVADeductible->getMontantFinal() + $montantTva);
  
        // 2 - On créditer enfin le compte fournisseur
        $compteFournisseur->setMontantFinal($compteFournisseur->getMontantFinal() + $montantTva);
  
        // 3 - Troisième et dernière étape, on écrit dans le journal
        $derniereEcriture = $manager->getRepository(ComptaEcriture::class)->last_saved();
        $reference        = $this->generateReferenceEcriture($derniereEcriture, 2);
        $label            = "TVA à récupérer sur achat de marchandises";
        $tva              = 0;
        $montant          = $montantTva;
        $remarque         = null;
        $compteADebiter   = $compteTVADeductible;
        $compteAcrediter  = $compteFournisseur;
        $ecriture = $this->genererNouvelleEcritureDuJournal($exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque);
        $manager->persist($ecriture);
      }

      try{
        $manager->flush();
      } 
      catch(\Exception $e){
        $this->addFlash('danger', $e->getMessage());
      }
    }


    public function ecritureDeReglementsFournisseursDansLeJournalComptable(EntityManagerInterface $manager, int $mode, int $montant, object $exercice, \DateTime $date, ProviderSettlement $settlement)
    {
      $exerciceId = $exercice->getId();
      $referenceCommande = $settlement->getCommande()->getReference();

      // 1 - On commence par débiter le compte fournisseur
      $compteFournisseur = $manager->getRepository(ComptaCompteExercice::class)->findCompte(14, $exerciceId);
      $compteADebiter    = $compteFournisseur->setMontantFinal($compteFournisseur->getMontantFinal() - $montant);
       
      // 2 - On crédite ensuite soit le compte caisse, soit le compte banque
      if($mode == 1)
        $compteAcrediter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(13, $exerciceId);
      elseif($mode == 2)
        $compteAcrediter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(12, $exerciceId);

      $compteAcrediter->setMontantFinal($compteAcrediter->getMontantFinal() - $montant);

      // 3 - Et enfin la dernière étape, on écrit dans le journal
      $derniereEcriture = $manager->getRepository(ComptaEcriture::class)->last_saved();
      $reference = $this->generateReferenceEcriture($derniereEcriture);

      $label = "Règelement fournisseur commande N°$referenceCommande";
      $tva = 0;
      $remarque = null;
      $ecriture_liee_a = $settlement;

      $ecriture = $this->genererNouvelleEcritureDuJournal($exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque, $ecriture_liee_a);
      $manager->persist($ecriture);

      try{
        $manager->flush();
      } 
      catch(\Exception $e){
        $this->addFlash('danger', $e->getMessage());
      }
    }


    public function ecritureDesChargesDUnAchatDansLeJournalComptable(EntityManagerInterface $manager, int $transport, int $dedouanement, int $currency_cost, int $forwarding_cost, int $additional_fees, ProviderCommande $providerCommande, ComptaExercice $exercice)
    {
      /**
       * Pour chaque depense, on va créditer la caisse et débiter le compte autres charges
       *
       * @param ComptaExercice $exercice
       * @param string $reference
       * @param \DateTime $date
       * @param string $label
       * @param ComptaCompteExercice $compteADebiter
       * @param ComptaCompteExercice $compteAcrediter
       * @param integer $tva
       * @param integer $montant
       * @param string $remarque
       * @param [type] $ecriture_liee_a
       * @return void
       */
      // 1 Pour le transport
      $exerciceId = $exercice->getId();
      $totalCharges = $transport + $dedouanement + $currency_cost + $forwarding_cost + $additional_fees;

      $compteCaisse   = $manager->getRepository(ComptaCompteExercice::class)->findCompte(13, $exerciceId);
      $compteResultat = $manager->getRepository(ComptaCompteExercice::class)->findCompte(7, $exerciceId);

      $compteCaisse->setMontantFinal($compteCaisse->getMontantFinal() - $totalCharges);
      $compteResultat->setMontantFinal($compteResultat->getMontantFinal() - $totalCharges);

      // 3 - On va maintenant créditer le compte Charges HAO
      $compteChargesHAO = $manager->getRepository(ComptaCompteExercice::class)->findCompte(23, $exerciceId);
      $compteChargesHAO->setMontantFinal($compteChargesHAO->getMontantFinal() + $totalCharges);

      // 4 - Quatième et dernière étape, on écrit dans le journal
      $derniereEcriture = $manager->getRepository(ComptaEcriture::class)->last_saved();
      $reference = $this->generateReferenceEcriture($derniereEcriture);

      $label           = "Vente de marchandises";
      $tva             = 0;
      $date            = new \DateTime();
      $montant         = $totalCharges;
      $remarque        = null;
      $compteADebiter  = $compteResultat;
      $compteAcrediter = $compteCaisse;
      $ecriture = $this->genererNouvelleEcritureDuJournal($exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque);
      $manager->persist($ecriture);

      try{
        $manager->flush();
      } 
      catch(\Exception $e){
        $this->addFlash('danger', $e->getMessage());
      }

    }


    public function ecritureDeDepensesDansLeJournalComptable(EntityManagerInterface $manager, int $montant, int $mode, Depense $depense, $label, ComptaExercice $exercice)
    {
      $exerciceId = $exercice->getId();

      // $compteAutresCharges = $manager->getRepository(ComptaCompteExercice::class)->findCompte(27, $exerciceId);
      $compteResultat = $manager->getRepository(ComptaCompteExercice::class)->findCompte(7, $exerciceId);
      $compteResultat->setMontantFinal($compteResultat->getMontantFinal() - $montant);

      // 2 - On débiter ensuite soit le compte caisse, soit le compte banque
      if($mode == 1)
        $compteACrediter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(13, $exerciceId);
      elseif($mode == 2)
        $compteACrediter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(12, $exerciceId);

      $compteACrediter->setMontantFinal($compteACrediter->getMontantFinal() - $montant);

      // 3 - On va maintenant créditer le compte Charges HAO
      $compteChargesHAO = $manager->getRepository(ComptaCompteExercice::class)->findCompte(23, $exerciceId);
      $compteChargesHAO->setMontantFinal($compteChargesHAO->getMontantFinal() + $montant);



      // 5 - Et la dernière étape pour finir, on écrit dans le journal
      $derniereEcriture = $manager->getRepository(ComptaEcriture::class)->last_saved();
      $reference = $this->generateReferenceEcriture($derniereEcriture);

      $tva             = 0;
      $date            = new \DateTime();
      $remarque        = null;
      $compteADebiter  = $compteResultat;
      $compteAcrediter = $compteACrediter;
      $ecriture_liee_a = $depense;
      $ecriture = $this->genererNouvelleEcritureDuJournal($exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque, $ecriture_liee_a);
      $manager->persist($ecriture);

      try{
        $manager->flush();
      } 
      catch(\Exception $e){
        $this->addFlash('danger', $e->getMessage());
      }

    }


    public function determinationDuResultatDeLExercice(EntityManagerInterface $manager, ComptaExercice $exercice)
    {
      // On va sélectionner tous les comptes de l'actif et tous les comptes des produits
      // Ensuite, on sélectionne tous les comptes du passif sauf le compte résultat et tous les comptes des charges
      $exerciceId = $exercice->getId();
      $comptes = $exercice->getComptaCompteExercices();
      $sommesActifs  = 0;
      $sommesPassifs = 0;
      foreach ($comptes as $compte) {
        $typeCompteId = $compte->getCompte()->getClasse()->getType()->getId();
        if($typeCompteId == 4 and ($compte->getCompte()->getIsDeleted() == 0 and $compte->getIsDeleted() == 0)){
          $sommesActifs = $sommesActifs + $compte->getMontantFinal();
        }

        if($typeCompteId == 3 and $compte->getCompte()->getId() != 7 and ($compte->getCompte()->getIsDeleted() == 0 and $compte->getIsDeleted() == 0)){
          $sommesPassifs = $sommesPassifs + $compte->getMontantFinal();
        }
      }

      $resultat = $sommesActifs - $sommesPassifs;
      $compteREsultat = $manager->getRepository(ComptaCompteExercice::class)->findCompte(7, $exerciceId);
      $compteREsultat->setMontantFinal($resultat);
    }


    public function genererNouvelleEcritureDuJournal(ComptaExercice $exercice, string $reference, \DateTime $date, string $label, ComptaCompteExercice $compteADebiter, ComptaCompteExercice $compteAcrediter, int $tva, int $montant, string $remarque = null, $ecriture_liee_a = null)
    {
      /**
       * Cette fonction permet de créer une nouvelle entité écriture. Vu qu'une écriture peut liée à une vente, 
       * un achat, un règelement client ou fournisseur, il va falloir vérifier à chaque fois le paramètre $ecriture_liee_a
       */ 
      $ecriture = new ComptaEcriture();
      $ecriture->setExercice($exercice);
      $ecriture->setNumero($reference);
      $ecriture->setDate($date);
      $ecriture->setLabel($label);
      $ecriture->setDebit($compteADebiter);
      $ecriture->setCredit($compteAcrediter);
      $ecriture->setTva($tva);
      $ecriture->setMontant($montant);
      $ecriture->setRemarque($remarque);
      $ecriture->setCreatedBy($this->getUser());
      if ($ecriture_liee_a instanceof Settlement) {
        $ecriture->setReglementClient($ecriture_liee_a);
      }
      elseif ($ecriture_liee_a instanceof ProviderSettlement) {
        $ecriture->setRegelementFournisseur($ecriture_liee_a);
      }
      elseif ($ecriture_liee_a instanceof CustomerCommande) {
        $ecriture->setVente($ecriture_liee_a);
      }
      elseif ($ecriture_liee_a instanceof ProviderCommande) {
        $ecriture->setAchat($ecriture_liee_a);
      }
      elseif ($ecriture_liee_a instanceof Depense) {
        $ecriture->setDepense($ecriture_liee_a);
      }

      return $ecriture;
    }


    public function generateReferenceEcriture(object $object = null, int $increment = 1)
    {
      $prefix = "SM-";
      
      if(!empty($object))
      {
        $zero = "";
        $number = (int) substr($object->getNumero(), 3);
        $numero_ordre = $number + $increment;
        if(strlen($numero_ordre) == 1){
          $zero = '00';
        } 
        elseif (strlen($numero_ordre) == 2) {
          $zero = '0';
        }
        $reference = $prefix.$zero.$numero_ordre;
      }
      else{
        $reference = "SM-001";            
      }
      return $reference;
    }
}