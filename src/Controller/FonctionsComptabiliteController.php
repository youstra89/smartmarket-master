<?php

namespace App\Controller;

use App\Entity\ComptaEcriture;
use App\Entity\ComptaCompteExercice;
use App\Entity\CustomerCommande;
use App\Entity\Settlement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class FonctionsComptabiliteController extends AbstractController
{
    // Cette fonction permet de mouvementer les comptes lors d'une achat de marchandises
    public function EcritureDeVenteDansLeJournalComptable(EntityManagerInterface $manager, int $totalMarchandises, int $tva, object $exercice, \DateTime $date, CustomerCommande $vente)
    {
      $exerciceId = $exercice->getId();
      $tva = ($totalMarchandises * $tva) / 100;

      // 1 - On commence par débiter le compte marchandise
      $compteMarchandise = $manager->getRepository(ComptaCompteExercice::class)->findCompte(11, $exerciceId);
      $compteMarchandise->setMontantFinal($compteMarchandise->getMontantFinal() - $totalMarchandises);

      // 2 - On crédite enfin le compte client
      $compteClient = $manager->getRepository(ComptaCompteExercice::class)->findCompte(9, $exerciceId);
      $compteClient->setMontantFinal($compteClient->getMontantFinal() + $totalMarchandises);

      // 3 - On va maintenant créditer le compte Vente de marchandises
      $compteVenteMarchandises = $manager->getRepository(ComptaCompteExercice::class)->findCompte(25, $exerciceId);
      $compteVenteMarchandises->setMontantFinal($compteVenteMarchandises->getMontantFinal() + $totalMarchandises);

      // 4 - Quatième et dernière étape, on écrit dans le journal
      $derniereEcriture = $manager->getRepository(ComptaEcriture::class)->last_saved();
      $reference = $this->generateReferenceEcriture($derniereEcriture);

      $ecriture = new ComptaEcriture();
      $ecriture->setExercice($exercice);
      $ecriture->setNumero($reference);
      $ecriture->setVente($vente);
      $ecriture->setDate($date);
      $ecriture->setLabel("Vente de marchandises");
      $ecriture->setDebit($compteMarchandise);
      $ecriture->setCredit($compteClient);
      $ecriture->setTva(0);
      $ecriture->setMontant($totalMarchandises);
      $ecriture->setRemarque(null);
      // $ecriture->setIsEditable(true);
      $ecriture->setCreatedBy($this->getUser());
      $manager->persist($ecriture);


      if($tva != 0){
        // 1 - On débite le compte TVA du montant de la TVA si et seulement si la TVA n'est pas nulle
        $compteTVACollectee = $manager->getRepository(ComptaCompteExercice::class)->findCompte(17, $exerciceId);
        $compteTVACollectee->setMontantFinal($compteTVACollectee->getMontantFinal() + $tva);
  
        // 2 - On crédite enfin le compte client
        // $compteClient = $manager->getRepository(ComptaCompteExercice::class)->findCompte(9, $exerciceId);
        $compteClient->setMontantFinal($compteClient->getMontantFinal() + $tva);
  
        // 3 - Troisième et dernière étape, on écrit dans le journal
        $derniereEcriture = $manager->getRepository(ComptaEcriture::class)->last_saved();
        $reference = $this->generateReferenceEcriture($derniereEcriture);

        $ecriture = new ComptaEcriture();
        $ecriture->setExercice($exercice);
        $ecriture->setNumero($reference);
        $ecriture->setDate($date);
        $ecriture->setLabel("TVA collectée sur vente de marchandises");
        $ecriture->setDebit($compteTVACollectee);
        $ecriture->setCredit($compteClient);
        $ecriture->setTva(0);
        $ecriture->setMontant($tva);
        $ecriture->setRemarque(null);
        // $ecriture->setIsEditable(true);
        $ecriture->setCreatedBy($this->getUser());
        $manager->persist($ecriture);
      }

      try{
        $manager->flush();
      } 
      catch(\Exception $e){
        $this->addFlash('danger', $e->getMessage());
      }
    }


    public function EcritureDeReglementsClientsDansLeJournalComptable(EntityManagerInterface $manager, int $mode, int $montant, object $exercice, \DateTime $date, Settlement $settlement)
    {
      $exerciceId = $exercice->getId();
      $referenceCommande = $settlement->getCommande()->getReference();

      // 1 - On commence par débiter le compte client
      $compteClient = $manager->getRepository(ComptaCompteExercice::class)->findCompte(9, $exerciceId);
      $compteClient->setMontantFinal($compteClient->getMontantFinal() - $montant);
      
      // 2 - On crédite ensuite soit le compte caisse, soit le compte banque
      if($mode == 1)
        $compteACrediter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(13, $exerciceId);
      elseif($mode == 2)
        $compteACrediter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(12, $exerciceId);

      $compteACrediter->setMontantFinal($compteACrediter->getMontantFinal() + $montant);

      // 3 - Et enfin la dernière étape, on écrit dans le journal
      $derniereEcriture = $manager->getRepository(ComptaEcriture::class)->last_saved();
      $reference = $this->generateReferenceEcriture($derniereEcriture);

      $ecriture = new ComptaEcriture();
      $ecriture->setExercice($exercice);
      $ecriture->setNumero($reference);
      $ecriture->setReglementClient($settlement);
      $ecriture->setDate($date);
      $ecriture->setLabel("Règlement de la commande N°$referenceCommande");
      $ecriture->setDebit($compteClient);
      $ecriture->setCredit($compteACrediter);
      $ecriture->setTva(0);
      $ecriture->setMontant($montant);
      $ecriture->setRemarque(null);
      // $ecriture->setIsEditable(true);
      $ecriture->setCreatedBy($this->getUser());
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
      $nouveauMontant = $vente->getTotalAmount();
      $referenceVente = $vente->getReference();
      $exercice       = $vente->getExercice();
      $exerciceId     = $exercice->getId();

      /**
       * Si le nouveau montant de le vente est supérieur à l'ancien, alors il y a eu augmentation de la vente.
       * Dans ce cas, on va débiter le compte marchanise de la différence entre $nouveauMontant et $ancienMontant et créditer le compte client
       */
      if($nouveauMontant > $ancienMontant)
      {
        $montant = $nouveauMontant - $ancienMontant;

        // 1 - On commence par débiter le compte marchandise
        $compteMarchandise = $manager->getRepository(ComptaCompteExercice::class)->findCompte(11, $exerciceId);
        $compteMarchandise->setMontantFinal($compteMarchandise->getMontantFinal() - $montant);

        // 2 - On crédite enfin le compte client
        $compteClient = $manager->getRepository(ComptaCompteExercice::class)->findCompte(9, $exerciceId);
        $compteClient->setMontantFinal($compteClient->getMontantFinal() + $montant);

        // 3 - On va maintenant créditer le compte Vente de marchandises
        $compteVenteMarchandises = $manager->getRepository(ComptaCompteExercice::class)->findCompte(25, $exerciceId);
        $compteVenteMarchandises->setMontantFinal($compteVenteMarchandises->getMontantFinal() + $montant);

        // 4 - Quatième et dernière étape, on écrit dans le journal
        $derniereEcriture = $manager->getRepository(ComptaEcriture::class)->last_saved();
        $reference = $this->generateReferenceEcriture($derniereEcriture);

        $ecriture = new ComptaEcriture();
        $ecriture->setExercice($exercice);
        $ecriture->setNumero($reference);
        $ecriture->setVente($vente);
        $ecriture->setDate(new \DateTime());
        $ecriture->setLabel("Vente de marchandises (augmentation de la vente N°$referenceVente)");
        $ecriture->setDebit($compteMarchandise);
        $ecriture->setCredit($compteClient);
        $ecriture->setTva(0);
        $ecriture->setMontant($montant);
        $ecriture->setRemarque(null);
        $ecriture->setCreatedBy($this->getUser());
        $manager->persist($ecriture);
      }

      /**
       * Sinon, ce sera une diminution. Ce qui veut dire que le client à retourner certaines marchandises.
       * On débite alors le compte client et on crédite le compte marchandises.
       */
      if($ancienMontant > $nouveauMontant)
      {
        $montant = $ancienMontant - $nouveauMontant;

        // 1 - On commence par débiter le compte marchandise
        $compteMarchandise = $manager->getRepository(ComptaCompteExercice::class)->findCompte(11, $exerciceId);
        $compteMarchandise->setMontantFinal($compteMarchandise->getMontantFinal() + $montant);

        // 2 - On crédite enfin le compte client
        $compteClient = $manager->getRepository(ComptaCompteExercice::class)->findCompte(9, $exerciceId);
        $compteClient->setMontantFinal($compteClient->getMontantFinal() - $montant);

        // 3 - On va maintenant créditer le compte Achat de marchandises
        $compteAchatMarchandises = $manager->getRepository(ComptaCompteExercice::class)->findCompte(25, $exerciceId);
        $compteAchatMarchandises->setMontantFinal($compteAchatMarchandises->getMontantFinal() + $montant);

        // 4 - Quatième et dernière étape, on écrit dans le journal
        $derniereEcriture = $manager->getRepository(ComptaEcriture::class)->last_saved();
        $reference = $this->generateReferenceEcriture($derniereEcriture);

        $ecriture = new ComptaEcriture();
        $ecriture->setExercice($exercice);
        $ecriture->setNumero($reference);
        $ecriture->setVente($vente);
        $ecriture->setDate(new \DateTime());
        $ecriture->setLabel("Retour de marchandises (diminution de la vente N°$referenceVente)");
        $ecriture->setDebit($compteClient);
        $ecriture->setCredit($compteMarchandise);
        $ecriture->setTva(0);
        $ecriture->setMontant($montant);
        $ecriture->setRemarque(null);
        $ecriture->setCreatedBy($this->getUser());
        $manager->persist($ecriture);
      }

      try{
        $manager->flush();
      } 
      catch(\Exception $e){
        $this->addFlash('danger', $e->getMessage());
      }
    }


    public function generateReferenceEcriture(object $object = null)
    {
      $prefix = "SM-";
      
      if(!empty($object))
      {
        $zero = "";
        $number = (int) substr($object->getNumero(), 3);
        $numero_ordre = $number + 1;
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
