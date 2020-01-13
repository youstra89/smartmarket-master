<?php

namespace App\Controller\Admin;

use App\Entity\Depense;
use App\Form\DepenseType;
use App\Service\CheckConnectedUser;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
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
    public function index(ObjectManager $manager, CheckConnectedUser $checker)
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
    public function add(Request $request, ObjectManager $manager)
    {
        $depense = new Depense();
        $form = $this->createForm(DepenseType::class, $depense);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
          $data = $request->request->all();
          if(isset($data['date']))
          {
            $date = new \DateTime($data['date']);
            $mois = $date->format("Y-m");
            $depense->setDateDepense($date);
            $depense->setCreatedAt(new \DateTime());
            $depense->setCreatedBy($this->getUser());
            $manager->persist($depense);
            try{
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
    public function depenses_du_mois(Request $request, ObjectManager $manager, CheckConnectedUser $checker)
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
        return $this->render('Admin/Depense/depenses-journalieres-du-mois.html.twig', [
          'depenses'  => $depenses,
          'current' => 'accounting',
        ]);
    }

    /**
     * @Route("/depenses-mensuelles", name="depenses_mensuelles")
     */
    public function depenses_mensuelles(ObjectManager $manager, CheckConnectedUser $checker)
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
     * @Route("/comptabilite-les-débits", name="accountingdebtor")
     */
    public function debits(ObjectManager $manager, CheckConnectedUser $checker)
    {
        if($checker->getAccess() == true)
          return $this->redirectToRoute('login');

        $repoCommande  = $manager->getRepository(Commande  ::class);
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
}
