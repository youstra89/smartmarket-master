<?php

namespace App\Controller\Comptabilite;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Acompte;
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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/admin/comptabilite")
 * @Security("has_role('ROLE_ADMIN')")
 */
class AcomptesController extends AbstractController
{
    /**
     * @Route("/ajouter-acompte-client", name="add_acompte_client")
     */
    public function add_acompte_client(Request $request, EntityManagerInterface $manager, CheckConnectedUser $checker, FonctionsComptabiliteController $fonctions)
    {
      $customers = $manager->getRepository(Customer::class)->findAll();
      $exercice  = $fonctions->exercice_en_cours($manager);
      $exerciceId = $exercice->getId();
      if($request->isMethod('post'))
      {
        $data = $request->request->all();
        // return new Response(var_dump($data)); 
        if(!empty($data['token']))
        {
          $token = $data['token'];
          if($this->isCsrfTokenValid('acompte_client', $token)){
            $data = $request->request->all();
            if(empty($data['date'])){
              $this->addFlash('danger', 'Impossible d\'enregistrer une opération sans la date.');
              return $this->redirectToRoute('add_acompte_client');
            }
            else{
              $date      = new \DateTime($data["date"]);
              $mode      = $data["mode"];
              $montant   = (int) $data["montant"];
              $remarque  = $data["remarque"];
              $customerId = (int) $data["customer"];
              $customer  = $manager->getRepository(Customer::class)->find($customerId);

              $acompte = new Acompte();
              $acompte->setCustomer($customer);
              $acompte->setDate($date);
              $acompte->setModePaiement($mode);
              $acompte->setMontant($montant);
              $acompte->setExercice($exercice);
              $acompte->setCommentaire($remarque);
              $acompte->setCreatedBy($this->getUser());
              $manager->persist($acompte);
              $customer->setAcompte($customer->getAcompte() + $montant);
              /**
               * On va enregister une écriture dans le journal. Il s'agira dans un premier temps de débiter le compte caisse ou banque selon $mode
               * Dans un second temps, on va créditer le compte "Clients - Avances et acomptes récus"
               */
              // // $compteAutresCharges = $manager->getRepository(ComptaCompteExercice::class)->findCompte(27, $exerciceId);
              // $compteAcompteClients = $manager->getRepository(ComptaCompteExercice::class)->findCompte(27, $exerciceId);
              // $compteAcompteClients->setMontantFinal($compteAcompteClients->getMontantFinal() + $montant);

              // // 2 - On débiter ensuite soit le compte caisse, soit le compte banque
              // if($mode == 1)
              //   $compteADebiter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(13, $exerciceId);
              // elseif($mode == 2)
              //   $compteADebiter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(12, $exerciceId);

              // $compteADebiter->setMontantFinal($compteADebiter->getMontantFinal() + $montant);
              // $label = "Versement d'acompte/avance pour le clien ".$customer->getNom();
              // $reference = $fonctions->generateReferenceEcriture($manager);
              // $ecriture  = $fonctions->genererNouvelleEcritureDuJournal($manager, $exercice, $reference, $date, $label, $compteADebiter, $compteAcompteClients, 0, $montant, $remarque);
              // $manager->persist($ecriture);
              // dd($acompte);

              try{
                $manager->flush();
                $this->addFlash('success', 'Acompte enregistré avec succès pour le client <strong>'.$customer->getNom().'</strong>.');
              } 
              catch(\Exception $e){
                $this->addFlash('danger', $e->getMessage());
              }
              return $this->redirectToRoute('customer_info', ["id" => $customerId]);
            }
          }
          else{
            $this->addFlash('danger', 'Jéton de sécurité invalide. Vous avez certement mis trop de temps sur cette page.');
          }
        }
      }
      return $this->render('Comptabilite/enregistrer-acompte-client.html.twig', [
        'current'   => 'accounting',
        'customers'   => $customers,
      ]);
    }


    /**
     * @Route("/ajouter-acompte-fournisseur", name="add_acompte_fournisseur")
     */
    public function add_acompte_fournisseur(Request $request, EntityManagerInterface $manager, CheckConnectedUser $checker, FonctionsComptabiliteController $fonctions)
    {
      $providers = $manager->getRepository(Provider::class)->findAll();
      $exercice  = $fonctions->exercice_en_cours($manager);
      $exerciceId = $exercice->getId();
      if($request->isMethod('post'))
      {
        $data = $request->request->all();
        // return new Response(var_dump($data));
        if(!empty($data['token']))
        {
          $token = $data['token'];
          if($this->isCsrfTokenValid('acompte_fournisseur', $token)){
            $data = $request->request->all();
            if(empty($data['date'])){
              $this->addFlash('danger', 'Impossible d\'enregistrer une opération sans la date.');
              return $this->redirectToRoute('add_acompte_fournisseur');
            }
            else{
              $date      = new \DateTime($data["date"]);
              $mode      = $data["mode"];
              $montant   = (int) $data["montant"];
              $remarque  = $data["remarque"];
              $providerId = (int) $data["provider"];
              $provider  = $manager->getRepository(Provider::class)->find($providerId);
              $acompte = new Acompte();
              $acompte->setProvider($provider);
              $acompte->setDate($date);
              $acompte->setModePaiement($mode);
              $acompte->setMontant($montant);
              $acompte->setExercice($exercice);
              $acompte->setCommentaire($remarque);
              $acompte->setCreatedBy($this->getUser());
              $manager->persist($acompte);
              $provider->setAcompte($provider->getAcompte() + $montant);
              /**
               * On va enregister une écriture dans le journal. Il s'agira dans un premier temps de créditer le compte caisse ou banque selon $mode
               * Dans un second temps, on va débiter le compte "Fournisseur - Avances et acomptes récus"
               */
              // // $compteAutresCharges = $manager->getRepository(ComptaCompteExercice::class)->findCompte(27, $exerciceId);
              // $compteAcompteFournisseurs = $manager->getRepository(ComptaCompteExercice::class)->findCompte(28, $exerciceId);
              // $compteAcompteFournisseurs->setMontantFinal($compteAcompteFournisseurs->getMontantFinal() + $montant);

              // // 2 - On débiter ensuite soit le compte caisse, soit le compte banque
              // if($mode == 1)
              //   $compteACrediter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(13, $exerciceId);
              // elseif($mode == 2)
              //   $compteACrediter = $manager->getRepository(ComptaCompteExercice::class)->findCompte(12, $exerciceId);

              // $compteACrediter->setMontantFinal($compteACrediter->getMontantFinal() - $montant);
              // $label = "Versement d'acompte/avance chez le fournisseur ".$provider->getNom();
              // $reference = $fonctions->generateReferenceEcriture($manager);
              // $ecriture  = $fonctions->genererNouvelleEcritureDuJournal($manager, $exercice, $reference, $date, $label, $compteAcompteFournisseurs, $compteACrediter, 0, $montant, $remarque);
              // $manager->persist($ecriture);

              try{
                $manager->flush();
                $this->addFlash('success', 'Acompte enregistré avec succès chez le fournisseur <strong>'.$provider->getNom().'</strong>.');
              } 
              catch(\Exception $e){
                $this->addFlash('danger', $e->getMessage());
              }
              return $this->redirectToRoute('purchase');
            }
          }
          else{
            $this->addFlash('danger', 'Jéton de sécurité invalide. Vous avez certement mis trop de temps sur cette page.');
          }
        }
      }
      return $this->render('Comptabilite/enregistrer-acompte-fournisseur.html.twig', [
        'current'   => 'accounting',
        'providers' => $providers,
      ]);
    }
}
