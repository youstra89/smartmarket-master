<?php

namespace App\Controller\Admin;

use App\Entity\Settlement;
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
     * @Route("/ventes-du-jour/{date}", name="vente.du.jour")
     */
    public function vente_du_jour(Request $request, ObjectManager $manager, $date, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $date = new \DateTime($date);
        $date = $date->format('Y-m-d');
        $ventes = $manager->getRepository(CustomerCommande::class)->dayCommande($date);
        // return new Response(var_dump($ventes));
        if(empty($ventes)){
          $this->addFlash('danger', 'La date saisie n\'est pas correcte.');
          return $this->redirectToRoute('dayly.accounting');
        }
        return $this->render('Admin/Accounting/vente-du-jour.html.twig', [
          'ventes'  => $ventes,
          'date'    => $date,
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
        $benefices = $manager->getRepository(CustomerCommandeDetails::class)->benefice_journalier($mois);
        // dd($benefices);
        if(empty($ventes)){
          $this->addFlash('error', 'La date sélectionnée n\'est pas correcte.');
          // return $this->redirectToRoute('dayly.accounting');
        }
        return $this->render('Admin/Accounting/comptabilite-journaliere.html.twig', [
          'ventes'    => $ventes,
          'benefices' => $benefices,
          'current'   => 'accounting',
        ]);
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
        $benefices = [];
        foreach ($mois as $key => $value) {
          $ventes = $repoCommande->monthSells($value['date']);
          $somme = 0;
          foreach ($ventes as $keyV => $valueV) {
            $somme += $valueV['somme'];
          }
          $benefices[$value['date']] = $manager->getRepository(CustomerCommandeDetails::class)->benefice_mensuel($value['date']);
          $gains[$value['date']] = $somme;
        }
        // dump($mois[1]['date'], $benefices, $gains);
        return $this->render('Admin/Accounting/comptabilite-mensuelle.html.twig', [
          'mois'      => $mois,
          'gains'     => $gains,
          'benefices' => $benefices,
          'current'   => 'accounting',
        ]);
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
        // dd($restesAPayer);
        
        if (!empty($reglements)) {
          // On va sélectionner les différents clients
          $customers = [];
          foreach ($commandes as $value) {
            $customer               = $value->getCustomer();
            $customerId             = $customer->getId();
            $customers[$customerId] = $customer;
          }
          
          foreach ($restesAPayer as $value) {
            $restes[$value["id"]] = $value["reste"];
          }
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
        $repoSettlement       = $manager->getRepository(ProviderSettlement      ::class);
        $commandes            = $repoProviderCommande->lesCreanciers();
        $reglements           = $repoSettlement->reglementsIncomplets();
        $nbrCommandes         = $repoProviderCommande->nombreCommandesDesCreanciers();
        $restesAPayer         = $repoSettlement->restesAPayer();
        if (!empty($reglements)) {
          // On va sélectionner les différents clients
          $providers = [];
          foreach ($commandes as $value) {
            $provider               = $value->getProvider();
            $providerId             = $provider->getId();
            $providers[$providerId] = $provider;
          }
          
          foreach ($restesAPayer as $value) {
            $restes[$value["id"]] = $value["reste"];
          }
        }
        // dump($reglements);
        return $this->render('Admin/Accounting/comptabilite-credit.html.twig', [
          'achats'       => $commandes,
          'reglements'   => $reglements,
          'current'      => 'accounting',
          'nbrCommandes' => $nbrCommandes,
          'restesAPayer' => $restes,
          'providers'    => $providers,
        ]);
    }
}
