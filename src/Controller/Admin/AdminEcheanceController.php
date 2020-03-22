<?php
// src/Controller/LuckyController.php
namespace App\Controller\Admin;

use App\Entity\Echeance;
use App\Entity\Settlement;
use App\Entity\CustomerCommande;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/admin/echeances")
 * @IsGranted("ROLE_VENTE")
 */
class AdminEcheanceController extends AbstractController
{
    /**
     * @Route("/", name="echeances")
     */
    public function index(ObjectManager $manager)
    {
        $echeances = $manager->getRepository(Echeance::class)->toutesLesEcheances();
        $dates = $manager->getRepository(Echeance::class)->differentesDatesEcheances();
        // dump($echeances);
        return $this->render('Admin/Echeance/index.html.twig', [
          'current'   => 'accounting',
          'dates'     => $dates,
          'echeances' => $echeances,
        ]);
    }

    /**
     * @Route("/add/{id}", name="echeance_add", requirements={"id"="\d+"})
     * @param CustomerCommande $commande
     */
    public function add(Request $request, ObjectManager $manager, CustomerCommande $commande, int $id)
    {
      if(null === $commande->getCustomer())
      {
        // Pour les commandes qui ne sont pas liées à un clients, il ne sera pas possible de d'enregistrer des échéances
        $this->addFlash('danger', 'Impossible de continuer, la commande doit être liée à un client.');
        return $this->redirectToRoute('settlement', ["id" => $id]);
      }

      if($request->isMethod('post'))
      {
        $data = $request->request->all();
        $token  = $data['token'];
        // return new Response(var_dump($data["dates"]));
        if($this->isCsrfTokenValid('token_dates_echeances', $token)){
          if(isset($data["dates"])){
            $dates = $data["dates"];
            foreach ($dates as $key => $value) {
              if(!empty($value))
              {
                $montant = isset($data["montants"]) ? (int) $data["montants"][$key] : 0;
                $echeance = new Echeance();
                $echeance->setCommande($commande);
                $echeance->setAmount($montant);
                $echeance->setDateEcheance(new \DateTime($value));
                $echeance->setCreatedBy($this->getUser());
                $echeance->setCreatedAt(new \DateTime());
                $manager->persist($echeance);
              }
            }
          }
          try{
            $manager->flush();
            $this->addFlash('success', 'Enregistrement d\'échéances de la commande <strong>'.$commande->getReference().'</strong> réussie.');
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          } 
          return $this->redirectToRoute('settlement', ["id" => $id]);
        }
      }
          
      return $this->render('Admin/Echeance/echeance-add.html.twig', [
        'current' => 'products',
        'commande' => $commande,
      ]);
    }


    /**
     * @Route("/edit/{id}", name="echeance_edit", requirements={"id"="\d+"})
     * @param Echeance $echeance
     */
    public function edit(Request $request, ObjectManager $manager, Echeance $echeance, int $id)
    {
      // dump($echeance);
      if($request->isMethod('post'))
      {
        $data = $request->request->all();
        $token  = $data['token'];
        // return new Response(var_dump($data["dates"]));
        if($this->isCsrfTokenValid('token_edit_echeance', $token)){
          $date    = $data["date"];
          $montant = $data["montant"];
          $echeance->setAmount($montant);
          $echeance->setDateEcheance(new \DateTime($date));
          $echeance->setUpdatedBy($this->getUser());
          $echeance->setUpdatedAt(new \DateTime());

          try{
            $manager->flush();
            $this->addFlash('success', 'Mise à jour de l\'échéance de la commande <strong>'.$echeance->getCommande()->getReference().'</strong> réussie.');
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          } 
          return $this->redirectToRoute('echeances');
        }
      }
          
      return $this->render('Admin/Echeance/echeance-edit.html.twig', [
        'current' => 'products',
        'echeance' => $echeance,
      ]);
    }


    /**
     * @Route("/enregistrer-un-versement-echeance/{id}", name="payer_echeance", requirements={"id"="\d+"})
     * @param Echeance $echeance
     */
    public function enregistrer_un_versement(Request $request, ObjectManager $manager, Echeance $echeance)
    {
      if (setlocale(LC_TIME, 'fr_FR') == '') {
        $format_jour = '%#d';
      } else {
        $format_jour = '%e';
      }
      setlocale (LC_TIME, 'fr_FR.utf8','fra'); 
      // strftime("%A $format_jour %B %Y", strtotime('2008-04-18'));
      $commandeId = $echeance->getCommande()->getId();
      $datePrevue = ucwords(strftime("%A $format_jour %B %Y", strtotime($echeance->getDateEcheance()->format("Y-m-d"))));

      if ($echeance->getAmount() == 0) {
        $this->addFlash('danger', 'Montant d\'écheance incorrect. Le montant doit être supérieur à 0');
        return $this->redirectToRoute('echeances');
      }

      // dump($echeance);
      if($request->isMethod('post'))
      {
        $data = $request->request->all();
        $token  = $data['token'];
        // return new Response(var_dump($data["dates"]));
        if($this->isCsrfTokenValid('token_payer_echeance', $token)){
          $date    = new \DateTime($data["date"]);
          // return new Response(var_dump($this->respectEcheance($date, $echeance)));
          $echeance->setEcheanceRespectee($this->respectEcheance($date, $echeance));
          $echeance->setIsPaid(true);
          $echeance->setDateSettlement($date);
          $echeance->setUpdatedBy($this->getUser());
          $echeance->setUpdatedAt(new \DateTime());

          // On enregistre aussi un nouveau $settlement
          $dernierVersement = $manager->getRepository(Settlement::class)->lastSettlement($commandeId);
          $reference = AdminSellController::generateInvoiceReference($manager);
          $settlementNumber = AdminSellController::generateSettlementNumber(empty($dernierVersement) ? null : $dernierVersement);
          $settlement = new Settlement();
          $settlement->setDate($date);
          $settlement->setReference($reference);
          $settlement->setAmount($echeance->getAmount());
          $settlement->setNumber($settlementNumber);
          $settlement->setReceiver($this->getUser());
          $settlement->setCreatedBy($this->getUser());
          $settlement->setCommande($echeance->getCommande());
          $manager->persist($settlement);

          try{
            $manager->flush();
            $this->addFlash('success', 'Enregistrement du paiement de l\'échéance de la commande <strong>'.$echeance->getCommande()->getReference().'</strong> réussie.');
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          } 
          return $this->redirectToRoute('customer.order.details', ['id' => $commandeId]);
        }
      }
          
      return $this->render('Admin/Echeance/payer-echeance.html.twig', [
        'current'    => 'products',
        'echeance'   => $echeance,
        'datePrevue' => $datePrevue,
      ]);
    }


    public function respectEcheance($date, $echeance)
    {
      return ($date <= $echeance->getDateEcheance());
    }

}
