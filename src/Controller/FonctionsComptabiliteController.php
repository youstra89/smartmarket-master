<?php

namespace App\Controller;

use App\Entity\Acompte;
use App\Entity\Depense;
use App\Entity\Settlement;
use App\Entity\ComptaCompte;
use App\Entity\ComptaEcriture;
use App\Entity\ComptaExercice;
use App\Entity\RetraitAcompte;
use App\Entity\CustomerCommande;
use App\Entity\ProviderCommande;
use App\Entity\ProviderSettlement;
use App\Entity\ComptaCompteExercice;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class FonctionsComptabiliteController extends AbstractController
{
    // Cette fonction permet de mouvementer les comptes lors d'une achat de marchandises
    public function ecriture_de_vente_dans_le_journal_comptable(EntityManagerInterface $manager, int $totalMarchandises, int $resultat, int $tva, object $exercice, \DateTime $date, CustomerCommande $vente)
    {
      $tableauEcritures = [];
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
      $reference = $this->generateReferenceEcriture($manager);

      $label           = "Vente de marchandises";
      $tva             = 0;
      $montant         = $totalMarchandises;
      $remarque        = null;
      $compteADebiter  = $compteClient;
      $compteAcrediter = $compteMarchandise;
      $ecriture_liee_a = $vente;
      $ecriture = $this->genererNouvelleEcritureDuJournal($manager, $exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque, $ecriture_liee_a);
      $manager->persist($ecriture);
      $tableauEcritures[] = $ecriture;



      if($montantTva != 0){
        // 1 - On créditer le compte TVA du montant de la TVA si et seulement si la TVA n'est pas nulle
        $compteTVACollectee = $manager->getRepository(ComptaCompteExercice::class)->findCompte(17, $exerciceId);
        $compteTVACollectee->setMontantFinal($compteTVACollectee->getMontantFinal() + $montantTva);
  
        // 2 - On débiter enfin le compte client
        $compteClient->setMontantFinal($compteClient->getMontantFinal() + $montantTva);
  
        // 3 - Troisième et dernière étape, on écrit dans le journal
        $reference        = $this->generateReferenceEcriture($manager, 2);
        $label            = "TVA collectée sur vente de marchandises";
        $tva              = 0;
        $montant          = $montantTva;
        $remarque         = null;
        $compteADebiter   = $compteClient;
        $compteAcrediter  = $compteTVACollectee;
        $ecriture = $this->genererNouvelleEcritureDuJournal($manager, $exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque);
        $manager->persist($ecriture);
        $tableauEcritures[] = $ecriture;

      }

      return $tableauEcritures;

      // try{
      //   $manager->flush();
      //   $response = true;
      // } 
      // catch(\Exception $e){
      //   $this->addFlash('danger', $e->getMessage());
      //   $response = false;
      // }
    }


    public function ecriture_du_resultat_de_vente_journal_comptable(EntityManagerInterface $manager, int $resultat, object $exercice, \DateTime $date, CustomerCommande $vente)
    {
      $tableauEcritures = [];
      $exerciceId = $exercice->getId();
      $compteResultat = $manager->getRepository(ComptaCompteExercice::class)->findCompte(7, $exerciceId);
      $compteResultat->setMontantFinal($compteResultat->getMontantFinal() + $resultat);
      $compteClient = $manager->getRepository(ComptaCompteExercice::class)->findCompte(9, $exerciceId);
      $compteClient->setMontantFinal($compteClient->getMontantFinal() + $resultat);
      $referenceCommande = $vente->getReference();

      
      // 5 - En cinquième et dernière étape, on écrit dans le journal
      $reference = $this->generateReferenceEcriture($manager);

      $label           = $resultat > 0 ? "Bénéfice sur vente de marchandises (commande N°$referenceCommande)" : "Perte sur vente de marchandises (commande N°$referenceCommande)";
      $tva             = 0;
      $montant         = $resultat;
      $remarque        = null;
      $compteADebiter  = $compteClient;
      $compteAcrediter = $compteResultat;
      $ecriture_liee_a = $vente;
      $ecriture = $this->genererNouvelleEcritureDuJournal($manager, $exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque, $ecriture_liee_a);
      $manager->persist($ecriture);
      $tableauEcritures[] = $ecriture;

      return $tableauEcritures;
    }


    public function ecriture_de_l_enregistrement_du_stock_initial_dans_le_journal_comptable(EntityManagerInterface $manager, int $montant, object $exercice)
    {
      $tableauEcritures = [];
      $exerciceId = $exercice->getId();
      $compteMarchandise = $manager->getRepository(ComptaCompteExercice::class)->findCompte(11, $exerciceId);
      $compteMarchandise->setMontantFinal($compteMarchandise->getMontantFinal() + $montant);
      $compteCapital = $manager->getRepository(ComptaCompteExercice::class)->findCompte(5, $exerciceId);
      $compteCapital->setMontantFinal($compteCapital->getMontantFinal() + $montant);

      
      // 5 - En cinquième et dernière étape, on écrit dans le journal
      $reference = $this->generateReferenceEcriture($manager);

      $label           = "Enregistrement de nouveaux stocks de produits";
      $tva             = 0;
      $remarque        = null;
      $compteADebiter  = $compteCapital;
      $compteAcrediter = $compteMarchandise;
      $ecriture_liee_a = null;
      $ecriture = $this->genererNouvelleEcritureDuJournal($manager, $exercice, $reference, new \DateTime(), $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque, $ecriture_liee_a);
      $manager->persist($ecriture);
      $tableauEcritures[] = $ecriture;

      return $tableauEcritures;
    }


    public function ecriture_des_avances_ou_des_creances(EntityManagerInterface $manager, int $montant, object $exercice, \DateTime $date, string $nom, bool $acomptes, string $type)
    {
      $tableauEcritures = [];
      $exerciceId = $exercice->getId();

      /**
       * La fonction me permet d'enregistrer les avances/acomptes (clients et fournisseurs), les créances clients et les arriérés fournisseurs.
       */
      if($type === "client" and $acomptes == 0){
        // Dans ce cas, il s'agit d'une créance initiale client. On débite le compte "Clients" et on crédite le compte "Capital"
        $label = "Créances du client $nom";
        $compteADebiter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(9, $exerciceId);
        $compteADebiter->setMontantFinal($compteADebiter->getMontantFinal() + $montant);
        $compteAcrediter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(5, $exerciceId);
        $compteAcrediter->setMontantFinal($compteAcrediter->getMontantFinal() + $montant);
      }
      elseif($type === "client" and $acomptes == 1){
        // Dans ce cas, il s'agit d'un acompte / avance initial reçu client. On débite le compte "Clients" et on crédite le compte "Capital"
        $label = "Acomptes/Avances du client $nom";
        $compteADebiter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(5, $exerciceId);
        $compteADebiter->setMontantFinal($compteADebiter->getMontantFinal() - $montant);
        $compteAcrediter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(27, $exerciceId);
        $compteAcrediter->setMontantFinal($compteAcrediter->getMontantFinal() + $montant);
      }
      elseif($type === "fournisseur" and $acomptes == 0){
        // Dans ce cas, il s'agit d'un arriéré initial fournisseur. On débite le compte débite le compte "Capital" et on crédite le compte "Fournisseur"
        $label = "Arriérés envers le fournisseur $nom";
        $compteADebiter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(5, $exerciceId);
        $compteADebiter->setMontantFinal($compteADebiter->getMontantFinal() - $montant);
        $compteAcrediter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(14, $exerciceId);
        $compteAcrediter->setMontantFinal($compteAcrediter->getMontantFinal() + $montant);
      }
      elseif($type === "fournisseur" and $acomptes == 1){
        // Dans ce cas, il s'agit d'un acompte / avance initial versé. On débite le compte "Fournisseurs - Avances/Acomptes versés" et on crédite le compte "Capital"
        $label = "Avances/Acomptes du fournisseur $nom";
        $compteADebiter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(28, $exerciceId);
        $compteADebiter->setMontantFinal($compteADebiter->getMontantFinal() + $montant);
        $compteAcrediter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(5, $exerciceId);
        $compteAcrediter->setMontantFinal($compteAcrediter->getMontantFinal() + $montant);
      }

      // 5 - En cinquième et dernière étape, on écrit dans le journal
      $reference = $this->generateReferenceEcriture($manager);
      $tva             = 0;
      $montant         = $montant;
      $remarque        = null;
      $ecriture = $this->genererNouvelleEcritureDuJournal($manager, $exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque);
      $manager->persist($ecriture);

      $tableauEcritures[] = $ecriture;

      return $tableauEcritures;
    }


    public function ecriture_de_reglements_clients_dans_le_journal_comptable(EntityManagerInterface $manager, int $mode, int $montant, object $exercice, \DateTime $date, Settlement $settlement, bool $paiementCreance = false)
    {
      $tableauEcritures = [];
      $exerciceId = $exercice->getId();

      /**
       * Le client peut règler une facture avec son acompte. Dans ce cas, on va débiter "Clients - Acomptes et avances reçues" et créditer le compte "Client"
       */
      if($mode == 3){
        $compteADebiter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(9, $exerciceId);
        $compteADebiter->setMontantFinal($compteADebiter->getMontantFinal() - $montant);

        $compteAcrediter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(27, $exerciceId);
        $compteAcrediter->setMontantFinal($compteAcrediter->getMontantFinal() - $montant);  
      }
      else{

        // 1 - On commence par crediter le compte client
        $compteAcrediter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(9, $exerciceId);
        $compteAcrediter->setMontantFinal($compteAcrediter->getMontantFinal() - $montant);
        
        // 2 - On débiter ensuite soit le compte caisse, soit le compte banque
        if($mode == 1)
          $compteADebiter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(13, $exerciceId);
        elseif($mode == 2)
          $compteADebiter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(12, $exerciceId);
        elseif($mode == 4)
          $compteADebiter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(29, $exerciceId);
  
        $compteADebiter->setMontantFinal($compteADebiter->getMontantFinal() + $montant);
      }


      // 3 - Et enfin la dernière étape, on écrit dans le journal
      $reference = $this->generateReferenceEcriture($manager);
      if($paiementCreance == 0){
        $referenceCommande = $settlement->getCommande()->getReference();
        $label           = $mode == 3 ?"Règlement de la commande N°$referenceCommande avec les avances/acomptes reçus" : "Règlement de la commande N°$referenceCommande";
      }
      else{
        $label = "Paiement de créance initiale";
      }
      $tva             = 0;
      $montant         = $montant;
      $remarque        = null;
      $compteADebiter  = $compteADebiter;
      $compteAcrediter = $compteAcrediter;
      $ecriture_liee_a = $settlement;
      $ecriture = $this->genererNouvelleEcritureDuJournal($manager, $exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque, $ecriture_liee_a);
      $manager->persist($ecriture);
      $tableauEcritures[] = $ecriture;

      return $tableauEcritures;

      // try{
      //   $manager->flush();
      //   $response = true;
      // } 
      // catch(\Exception $e){
      //   $this->addFlash('danger', $e->getMessage());
      //   $response = false;
      // }

      // return $response;
    }


    public function ecriture_de_retrait_acompte_client_dans_le_journal_comptable(EntityManagerInterface $manager, int $mode, int $montant, object $exercice, \DateTime $date, RetraitAcompte $retrait, string $nom)
    {
      $tableauEcritures = [];
      $exerciceId = $exercice->getId();

      // On débite le compte "Clients - Acomptes/Avances versés"
      $compteADebiter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(27, $exerciceId);
      $compteADebiter->setMontantFinal($compteADebiter->getMontantFinal() - $montant);
      
      // 2 - On crédite ensuite soit le compte caisse, soit le compte banque
      if($mode == 1)
        $compteACrediter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(13, $exerciceId);
      elseif($mode == 2)
        $compteACrediter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(12, $exerciceId);
      elseif($mode == 3)
        $compteACrediter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(27, $exerciceId);
      elseif($mode == 4)
        $compteACrediter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(29, $exerciceId);
      // dd($mode);
      $compteACrediter->setMontantFinal($compteACrediter->getMontantFinal() - $montant);


      // 3 - Et enfin la dernière étape, on écrit dans le journal
      $reference       = $this->generateReferenceEcriture($manager);
      $label           = "Remboursement d'acompte client (".$nom.")";
      $tva             = 0;
      $montant         = $montant;
      $remarque        = null;
      $compteADebiter  = $compteADebiter;
      $compteAcrediter = $compteACrediter;
      $ecriture_liee_a = $retrait;
      $ecriture = $this->genererNouvelleEcritureDuJournal($manager, $exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque, $ecriture_liee_a);
      $manager->persist($ecriture);
      $tableauEcritures[] = $ecriture;

      return $tableauEcritures;
    }


    public function ecritureDeModificationDeVente(EntityManagerInterface $manager, CustomerCommande $vente, int $ancienMontant)
    {
      $tva            = $vente->getTva();
      $nouveauMontant = $vente->getMontantTtc();
      $referenceVente = $vente->getReference();
      $exercice       = $vente->getExercice();
      $exerciceId     = $exercice->getId();
      $montant        = 0;
      $option         = "";
      $tableauEcritures = [];


      /**
       * Si le nouveau montant de le vente est supérieur à l'ancien, alors il y a eu augmentation de la vente.
       * Dans ce cas, on va débiter le compte marchanise de la différence entre $nouveauMontant et $ancienMontant et créditer le compte client
       */
      if($nouveauMontant > $ancienMontant)
      {
        $montant = $nouveauMontant - $ancienMontant;
        $option  = "augmentation";
        $reference = $this->generateReferenceEcriture($manager);

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
        $ecriture = $this->genererNouvelleEcritureDuJournal($manager, $exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque, $ecriture_liee_a);
        $manager->persist($ecriture);
        $tableauEcritures[] = $ecriture;
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
        $ecriture = $this->genererNouvelleEcritureDuJournal($manager, $exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque, $ecriture_liee_a);
        $manager->persist($ecriture);
        $tableauEcritures[] = $ecriture;
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
        $reference = $this->generateReferenceEcriture($manager, 2);

        $label           = "Vente de marchandises (diminution de la vente N°$referenceVente)";
        $tva             = 0;
        $date            = new \DateTime();
        $montant         = $montant;
        $remarque        = null;
        $compteADebiter  = $compteMarchandise;
        $compteAcrediter = $compteClient;
        $ecriture_liee_a = $vente;
        $ecriture = $this->genererNouvelleEcritureDuJournal($manager, $exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque, $ecriture_liee_a);
        $manager->persist($ecriture);
        $tableauEcritures[] = $ecriture;
      }

      return $tableauEcritures;
    }


    public function ecriture_du_retour_de_marchandises_apres_une_vente(EntityManagerInterface $manager, ComptaExercice $exercice, int $totalMarchandises, int $montantTva, int $resultat, CustomerCommande $vente)
    {
      $tableauEcritures = [];
      /** 
       * On va écrire dans le journal. 
       * Les comptes à débiter:
       *    1- Le compte marchandise va être débiter de la valeur hors taxe des marchandises retournées,
       *    2- le compte TVA débiter du montant de la TVA
       *    3- et le compte résultat sera débiter de la valeur du bénéfice (ou perte) réalisé lors de la vente
       * Les comptes à créditer
       *    1- Le compte "Clients" va être créditer du montant total hors taxe de 
       *    la  + TVA + resultat, sachant que = (bénéfice - remise)
       */
      $exerciceId = $exercice->getId();
      $referenceCommande = $vente->getReference();
      // $acompte = $vente->getCustomer()->getAcompte() + $totalMarchandises + $montantTva + $resultat;
      // $vente->getCustomer()->setAcompte($acompte);
      $date = new \DateTime();

      //##################### Première opération
      $compteMarchandise = $manager->getRepository(ComptaCompteExercice::class)->findCompte(11, $exerciceId);
      $compteMarchandise->setMontantFinal($compteMarchandise->getMontantFinal() + $totalMarchandises);
      $compteAcompteClient = $manager->getRepository(ComptaCompteExercice::class)->findCompte(13, $exerciceId);
      $compteAcompteClient->setMontantFinal($compteAcompteClient->getMontantFinal() - $totalMarchandises);

      $compteVenteMarchandises = $manager->getRepository(ComptaCompteExercice::class)->findCompte(25, $exerciceId);
      $compteVenteMarchandises->setMontantFinal($compteVenteMarchandises->getMontantFinal() - $resultat);

      $reference = $this->generateReferenceEcriture($manager);
      $label           = "Retour sur vente de marchandises (commande N°$referenceCommande)";
      $tva             = 0;
      $montant         = $totalMarchandises;
      $remarque        = null;
      $compteADebiter  = $compteMarchandise;
      $compteAcrediter = $compteAcompteClient;
      $ecriture_liee_a = $vente;
      $ecriture = $this->genererNouvelleEcritureDuJournal($manager, $exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque, $ecriture_liee_a);
      $manager->persist($ecriture);
      $tableauEcritures[] = $ecriture;

      //##################### Deuxième opération
      if($montantTva != 0){
        // 1 - On créditer le compte TVA du montant de la TVA si et seulement si la TVA n'est pas nulle
        $compteTVACollectee = $manager->getRepository(ComptaCompteExercice::class)->findCompte(17, $exerciceId);
        $compteTVACollectee->setMontantFinal($compteTVACollectee->getMontantFinal() - $montantTva);
  
        // 2 - On débiter enfin le compte client
        $compteAcompteClient->setMontantFinal($compteAcompteClient->getMontantFinal() - $montantTva);
  
        // 3 - Troisième et dernière étape, on écrit dans le journal
        $reference        = $this->generateReferenceEcriture($manager, 2);
        $label            = "Retour de TVA collectée sur vente de marchandises (commande N°$referenceCommande)";
        $tva              = 0;
        $montant          = $montantTva;
        $remarque         = null;
        $compteADebiter   = $compteTVACollectee;
        $compteAcrediter  = $compteAcompteClient;
        $ecriture = $this->genererNouvelleEcritureDuJournal($manager, $exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque);
        $manager->persist($ecriture);
        $tableauEcritures[] = $ecriture;
      }

      //######################## Troisième opération
      $compteResultat = $manager->getRepository(ComptaCompteExercice::class)->findCompte(7, $exerciceId);
      $compteResultat->setMontantFinal($compteResultat->getMontantFinal() - $resultat);
      $compteAcompteClient->setMontantFinal($compteAcompteClient->getMontantFinal() - $resultat);
      $reference = $this->generateReferenceEcriture($manager, 3);
      $label           = $resultat > 0 ? "Diminution du bénéfice dû à un retour sur vente de marchandises (commande N°$referenceCommande)" : "Recouvrement de perte dû à un retour sur vente de marchandises (commande N°$referenceCommande)";
      $tva             = 0;
      $montant         = $resultat;
      $remarque        = null;
      $compteADebiter  = $compteResultat;
      $compteAcrediter = $compteAcompteClient;
      $ecriture_liee_a = $vente;
      $ecriture = $this->genererNouvelleEcritureDuJournal($manager, $exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque, $ecriture_liee_a);
      $manager->persist($ecriture);
      $tableauEcritures[] = $ecriture;

      return $tableauEcritures;
      
    }


    public function ecritureDAchatDansLeJournalComptable(EntityManagerInterface $manager, int $totalMarchandises, int $tva, object $exercice, \DateTime $date, ProviderCommande $achat)
    {
      $tableauEcritures = [];
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
      $reference = $this->generateReferenceEcriture($manager);
      $label           = "Achat de marchandises";
      $tva             = 0;
      $montant         = $totalMarchandises;
      $remarque        = null;
      $compteADebiter  = $compteMarchandise;
      $compteAcrediter = $compteFournisseur;
      $ecriture_liee_a = $achat;
      $ecriture = $this->genererNouvelleEcritureDuJournal($manager, $exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque, $ecriture_liee_a);
      $manager->persist($ecriture);
      $tableauEcritures[] = $ecriture;


      if($montantTva != 0){
        // 1 - On debite le compte TVA du montant de la TVA si et seulement si la TVA n'est pas nulle
        $compteTVADeductible = $manager->getRepository(ComptaCompteExercice::class)->findCompte(10, $exerciceId);
        $compteTVADeductible->setMontantFinal($compteTVADeductible->getMontantFinal() + $montantTva);
  
        // 2 - On créditer enfin le compte fournisseur
        $compteFournisseur->setMontantFinal($compteFournisseur->getMontantFinal() + $montantTva);
  
        // 3 - Troisième et dernière étape, on écrit dans le journal
        $reference        = $this->generateReferenceEcriture($manager, 2);
        $label            = "TVA à récupérer sur achat de marchandises";
        $tva              = 0;
        $montant          = $montantTva;
        $remarque         = null;
        $compteADebiter   = $compteTVADeductible;
        $compteAcrediter  = $compteFournisseur;
        $ecriture = $this->genererNouvelleEcritureDuJournal($manager, $exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque);
        $manager->persist($ecriture);
        $tableauEcritures[] = $ecriture;
      }

      return $tableauEcritures;
    }


    public function ecritureDeReglementsFournisseursDansLeJournalComptable(EntityManagerInterface $manager, int $mode, int $montant, object $exercice, \DateTime $date, ProviderSettlement $settlement, bool $paiementArriere = false)
    {
      $tableauEcritures = [];
      $exerciceId = $exercice->getId();

      /**
       * Le client peut règler une facture avec son acompte. Dans ce cas, on va débiter le compte "Fournisseur" puis créditer "Fournisseur - Acomptes et avances versées"
       */
      if($mode == 3){
        $compteADebiter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(14, $exerciceId);
        $compteADebiter->setMontantFinal($compteADebiter->getMontantFinal() - $montant);

        $compteAcrediter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(28, $exerciceId);
        $compteAcrediter->setMontantFinal($compteAcrediter->getMontantFinal() - $montant);  

        // Il ne faut pas oublier de retirer le montant de l'acompte du client
        $settlement->getCommande()->getProvider()->setAcompte($settlement->getCommande()->getProvider()->getAcompte() - $montant);
      }
      else{
        // 1 - On commence par débiter le compte fournisseur
        $compteFournisseur = $manager->getRepository(ComptaCompteExercice::class)->findCompte(14, $exerciceId);
        $compteADebiter    = $compteFournisseur->setMontantFinal($compteFournisseur->getMontantFinal() - $montant);
         
        // 2 - On crédite ensuite soit le compte caisse, soit le compte banque
        if($mode == 1)
          $compteAcrediter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(13, $exerciceId);
        elseif($mode == 2)
          $compteAcrediter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(12, $exerciceId);
  
        $compteAcrediter->setMontantFinal($compteAcrediter->getMontantFinal() - $montant);
      }

      // 3 - Et enfin la dernière étape, on écrit dans le journal
      $reference = $this->generateReferenceEcriture($manager);

      if($paiementArriere == 0){
        $referenceCommande = $settlement->getCommande()->getReference();
        $label = $mode == 3 ? "Règelement fournisseur commande N°$referenceCommande avec les avances/acomptes versés" : "Règelement fournisseur commande N°$referenceCommande";
      }
      else{
        $label = "Paiement de arriéré initial";
      }
      $tva = 0;
      $remarque = null;
      $ecriture_liee_a = $settlement;

      $ecriture = $this->genererNouvelleEcritureDuJournal($manager, $exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque, $ecriture_liee_a);
      $manager->persist($ecriture);
      $tableauEcritures[] = $ecriture;

      return $tableauEcritures;
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

      $tableauEcritures = [];
      
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
      $reference = $this->generateReferenceEcriture($manager);

      $label           = "Vente de marchandises";
      $tva             = 0;
      $date            = new \DateTime();
      $montant         = $totalCharges;
      $remarque        = null;
      $compteADebiter  = $compteResultat;
      $compteAcrediter = $compteCaisse;
      $ecriture = $this->genererNouvelleEcritureDuJournal($manager, $exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque);
      $manager->persist($ecriture);
      $tableauEcritures[] = $ecriture;

      return $tableauEcritures;
    }


    public function ecritureDeDepensesDansLeJournalComptable(EntityManagerInterface $manager, int $typeDepenseId, int $montant, int $mode, Depense $depense, $label, ComptaExercice $exercice)
    {
      $tableauEcritures = [];
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
 
      /**
       * 3 - On va maintenant créditer l'un des comptes du résultat en fonction du type de la dépense à enregistrer
       *   $typeDepenseId = 1, il s'agit du paiement du salaire des employés;
       *   $typeDepenseId = 2, 3, 4, 5 ou 6 respectivement électricité, eau, loyer, nourriture ou impôt
       *   $typeDepenseId = 7 pour le transport
       *   $typeDepenseId = 8 pour les entretiens et réparations
       */
      if($typeDepenseId == 1){
        // On crédite le compte "Charge personnel"
        $compte = $manager->getRepository(ComptaCompteExercice::class)->findCompte(22, $exerciceId);
      }
      if($typeDepenseId != 1 && $typeDepenseId != 7 && $typeDepenseId != 8){
        // On crédite le compte "Charge HAO"
        $compte = $manager->getRepository(ComptaCompteExercice::class)->findCompte(23, $exerciceId);
      }
      if($typeDepenseId == 7){
        // On crédite le compte "Charge personnel"
        $compte = $manager->getRepository(ComptaCompteExercice::class)->findCompte(19, $exerciceId);
      }
      if($typeDepenseId == 8){
        // On crédite le compte "Charge personnel"
        $compte = $manager->getRepository(ComptaCompteExercice::class)->findCompte(20, $exerciceId);
      }
      $compte->setMontantFinal($compte->getMontantFinal() + $montant);



      // 5 - Et la dernière étape pour finir, on écrit dans le journal
      $reference = $this->generateReferenceEcriture($manager);

      $tva             = 0;
      $date            = new \DateTime();
      $remarque        = null;
      $compteADebiter  = $compteResultat;
      $compteAcrediter = $compteACrediter;
      $ecriture_liee_a = $depense;
      $ecriture = $this->genererNouvelleEcritureDuJournal($manager, $exercice, $reference, $date, $label, $compteADebiter, $compteAcrediter, $tva, $montant, $remarque, $ecriture_liee_a);
      $manager->persist($ecriture);
      $tableauEcritures[] = $ecriture;

      return $tableauEcritures;

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
      $compteResultat = $manager->getRepository(ComptaCompteExercice::class)->findCompte(7, $exerciceId);
      $compteResultat->setMontantFinal($resultat);
    }


    public function genererNouvelleEcritureDuJournal(EntityManagerInterface $manager, ComptaExercice $exercice, string $reference, \DateTime $date, string $label, ComptaCompteExercice $compteADebiter, ComptaCompteExercice $compteAcrediter, int $tva, int $montant, string $remarque = null, $ecriture_liee_a = null)
    {
      /**
       * Cette fonction permet de créer une nouvelle entité écriture. Vu qu'une écriture peut liée à une vente, 
       * un achat, un règelement client ou fournisseur, il va falloir vérifier à chaque fois le paramètre $ecriture_liee_a.
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
      elseif ($ecriture_liee_a instanceof Acompte) {
        $ecriture->setAcompte($ecriture_liee_a);
      }
      elseif ($ecriture_liee_a instanceof RetraitAcompte) {
        $ecriture->setRetraitAcompte($ecriture_liee_a);
      }

      return $ecriture;
    }


    public function exercice_en_cours($manager)
    {
      $exercice  = $manager->getRepository(ComptaExercice::class)->dernierExerciceEnCours();
      /*
       * Mais avant cela, on s'assurer que nous ne sommes pas en début de mois. Sinon, si nous sommes en début de mois et qu'il y a un exercice 
       * déjà enregistré, cela veut dire que l'exercice du mois qui vient de s'écoulé est fini. Auquel cas, il va falloir faire le bilan final 
       * du mois passé et enregistrer un nouvel exercice (avec bien entendu le bilan initial du mois en cours). Il ne faudra pas oublier de 
       * passé la valeur de $exercice->getAcheve() à true.
       */ 
      // $jourDuMois = (new \DateTime())->format("d");
      // if((int) $jourDuMois == 1){
        $date = new \DateTime();
        if($date->format("Y-m-d") > $exercice->getDateFin()->format("Y-m-d") and $exercice->getMois() != $date->format("m-Y")){
          $ancienExercice   = $exercice;
          $ancienExerciceId = $ancienExercice->getId();
          // 1- On va mettre reporter la valeur du compte résultat au compte réserve pour le nouvel exercice. 
          // 2- On passe la valeur du $exercice->getAcheve() à true, avec $exercice l'exercice qui vient à peine de finir.
          /**
           * 3- On enregistre un tout nouvel exercice et on enregistre aussi tous les comptes du bilan et tous les comptes du résultat. 
           *    Les valeurs initiales comptes du bilan de ce nouvel exercice seront exactement les mêmes que leurs valeurs finales sauf les
           *    comptes réserve et résultat. En effet, vu que la valeur du compte résultat va être ajouter au compte réserve, la valeur initiale
           *    du compte... 
           *    Trève de commantaire, passons à l'acte
           */
  
          // Première étape
          // Ajout du résultat aux réserves
          $compteReserve  = $manager->getRepository(ComptaCompteExercice::class)->findCompte(6, $ancienExerciceId);  
          $compteResultat = $manager->getRepository(ComptaCompteExercice::class)->findCompte(7, $ancienExerciceId);  
          $valeurCompteResultatNouvelExercice = $compteReserve->getMontantFinal() + $compteResultat->getMontantFinal();
          // Normalement, ce qui vient de se faire est une opération qui doit être ecrite dans le journal.
  
          // Deuxième étape
          $ancienExercice->setAcheve(true);
  
          // Troisième et dernière étape, on enregistre le nouvel exercice
          // On va commencer par créer un exercice
          $dateExercice  = (new \DateTime())->format("Y-m-d");
          $dateDebut     = new \DateTime(date('01-m-Y', strtotime($dateExercice)));
          $dateFin       = new \DateTime(date('t-m-Y', strtotime($dateExercice)));
          $labelExercice = $this->dateEnFrancais($dateDebut, false);
  
          $exercice = new ComptaExercice();
          $exercice->setDateDebut($dateDebut);
          $exercice->setDateFin($dateFin);
          $exercice->setLabel($labelExercice);
          $exercice->setMois($date->format("m-Y"));
          $exercice->setCreatedBy($this->getUser());
          $manager->persist($exercice);
  
          $comptesDuBilan  = $manager->getRepository(ComptaCompte::class)->comptesDuBilanOuDuResultat("bilan");
          foreach ($comptesDuBilan as $key => $compte) {
            // La valeur du compte réserve doit être calculer et la valeur du compte résultat est toujour 0
            $compteId = $compte->getId();
            if($compteId == 6){
              $montantCompte = $valeurCompteResultatNouvelExercice;
            }
            elseif($compteId == 7){
              $montantCompte = 0;
            }
            else{
              $montantCompte = $this->obtenirLaValeurDUnCompteLorsDeLExercicePrecedent($ancienExercice, $compteId);
            }
            $compteExercice = new ComptaCompteExercice();
            $compteExercice->setExercice($exercice);
            $compteExercice->setCompte($compte);
            $compteExercice->setMontantInitial($montantCompte);
            $compteExercice->setMontantFinal($montantCompte);
            $compteExercice->setCreatedBy($this->getUser());
  
            $manager->persist($compteExercice);
            // dd($compteExercice);
          }
  
          // Et enfin, les comptes du résutat
          $comptesResutats  = $manager->getRepository(ComptaCompte::class)->comptesDuBilanOuDuResultat("resultat");
          foreach ($comptesResutats as $key => $compte) {
            $compteExercice = new ComptaCompteExercice();
            $compteExercice->setExercice($exercice);
            $compteExercice->setCompte($compte);
            $compteExercice->setMontantInitial(0);
            $compteExercice->setMontantFinal(0);
            $compteExercice->setCreatedBy($this->getUser());
            $manager->persist($compteExercice);
            // dd($compteExercice);
          }
        }

        try{
          $manager->flush();
          return $exercice;
        } 
        catch(\Exception $e){
          $this->addFlash('danger', $e->getMessage());
        }
    }


    public function generateReferenceEcriture(EntityManagerInterface $manager, int $increment = 1)
    {
      $derniereEcriture = $manager->getRepository(ComptaEcriture::class)->last_saved();
      $prefix = "SM-";
      
      if(!empty($derniereEcriture))
      {
        $zero = "";
        $number = (int) substr($derniereEcriture->getNumero(), 3);
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


    public function obtenirLaValeurDUnCompteLorsDeLExercicePrecedent(ComptaExercice $exercice, int $compteId)
    {
      $montant = 0;
      foreach ($exercice->getComptaCompteExercices() as $value) {
        if($value->getCompte()->getId() == $compteId)
          $montant = $value->getMontantFinal();
      }
      return $montant;
    }
}
