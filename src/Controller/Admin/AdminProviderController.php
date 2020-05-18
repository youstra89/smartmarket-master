<?php
// src/Controller/LuckyController.php
namespace App\Controller\Admin;

use App\Entity\Provider;
use App\Form\ProviderType;
use App\Entity\ComptaExercice;
use App\Controller\FonctionsController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use App\Controller\FonctionsComptabiliteController;
use App\Entity\ProviderSettlement;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/admin/fournisseurs")
 */
class AdminProviderController extends AbstractController
{
  /**
   * @Route("/", name="provider")
   * @IsGranted("ROLE_ADMIN")
   */
   public function index(EntityManagerInterface $manager)
   {
       $providers = $manager->getRepository(Provider::class)->findAll();
       return $this->render('Admin/Provider/index.html.twig', [
         'current'  => 'purchases',
         'providers' => $providers
       ]);
   }

    /**
     * @Route("/add", name="provider.add")
     * @IsGranted("ROLE_ADMIN")
     */
    public function add(Request $request, EntityManagerInterface $manager, FonctionsController $fonctions, FonctionsComptabiliteController $fonctionsComptables)
    {
        $provider = new Provider();
        $form = $this->createForm(ProviderType::class, $provider);
        $form->handleRequest($request);
        $last_provider = $manager->getRepository(Provider::class)->last_saved_provider();
        $reference = $fonctions->generateReference("provider", $last_provider);
        if($form->isSubmitted() && $form->isValid())
        {
          $exercice  = $manager->getRepository(ComptaExercice::class)->dernierExerciceEnCours();
          $nom = $provider->getNom();
          $acompte = $provider->getAcompte();
          $arriere = $provider->getArriereInitial();
          if($acompte < 0 or $arriere < 0){
            $this->addFlash('danger', "Les valeurs de <strong>Avances</strong> et / ou <strong>Arriéré</strong> doivent être supérieur à zéro");
            return $this->redirectToRoute('provider.add');
          }
          // Lors de l'enregistrement d'un nouveau nouveau client, s'il y a des avances et ou des créances, il faut les ajouter aux ecritures comptables
          if($acompte > 0){
            $fonctionsComptables->ecritureDesAvancesOuDesCreancesInitiales($manager, $acompte, $exercice, new \DateTime(), $nom, true, "fournisseur");
          }
          if($arriere > 0){
            $fonctionsComptables->ecritureDesAvancesOuDesCreancesInitiales($manager, $arriere, $exercice, new \DateTime(), $nom, false, "fournisseur");
          }
          $provider->setCreatedBy($this->getUser());
          $manager->persist($provider);
          try{
            $manager->flush();
            $this->addFlash('success', 'Enregistrement du fournisseur <strong>'.$provider->getFirstname().' '.$provider->getLastname().'</strong> réussie.');
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          } 
          return $this->redirectToRoute('provider');
        }
        return $this->render('Admin/Provider/provider-add.html.twig', [
          'current' => 'purchases',
          'form'    => $form->createView(),
          'reference' => $reference,
        ]);
    }

    /**
     * @Route("/edit/{id}", name="provider.edit")
     * @IsGranted("ROLE_ADMIN")
     * @param Provider $provider
     */
    public function edit(Request $request, EntityManagerInterface $manager, Provider $provider)
    {
        $form = $this->createForm(ProviderType::class, $provider);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
          $provider->setUpdatedAt(new \DateTime());
          $provider->setUpdatedBy($this->getUser());
          $manager->persist($provider);
          try{
            $manager->flush();
            $this->addFlash('success', 'Mise à jour de <strong>'.$provider->getFirstname().' '.$provider->getLastname().'</strong> réussie.');
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          } 
          return $this->redirectToRoute('provider');
        }
        return $this->render('Admin/Provider/provider-edit.html.twig', [
          'current' => 'purchases',
          'provider' => $provider,
          'form'    => $form->createView()
        ]);
    }


    /**
     * @Route("/enregistrement-arriere-initial/{id}", name="paiement_arriere_initial", requirements={"id"="\d+"})
     *
     * @param Request $request
     * @param Provider $provider
     * @return void
     */
    public function paiement_arriere_initial(Request $request, EntityManagerInterface $manager, Provider $provider, int $id, FonctionsComptabiliteController $fonctions)
    {
      if(0 > $provider->getArriereInitial() or null == $provider->getArriereInitial()){
        $this->addFlash('danger', 'Aucun arriéré initial à payer pour ce fournisseur');
        return $this->redirectToRoute('provider');  
      }

      if($request->isMethod('post'))
      {
        $data = $request->request->all();
        // return new Response(var_dump($data));
        if(!empty($data['token']))
        {
          $token = $data['token'];
          if($this->isCsrfTokenValid('token_reglement_arriere_initial', $token)){
            $date   = new \DateTime($data['date']);
            $mode   = (int) $data['mode'];
            $amount = (int) $data['amount'];
            if($amount > $provider->getArriereInitial()){
              $this->addFlash('danger', 'Le montant saisie est supérieur au total de la créance initiale');
              return $this->redirectToRoute('paiement_arriere_initial', ["id" => $id]);  
            }
            $exercice  = $manager->getRepository(ComptaExercice::class)->dernierExerciceEnCours();
            $user = $this->getUser();
            $settlement = new ProviderSettlement();
            $settlement->setDate($date);
            $settlement->setModePaiement($mode);
            $settlement->setAmount($amount);
            $settlement->setNumber(0);
            $settlement->setReceiver($user);
            $settlement->setCreatedBy($this->getUser());
            $manager->persist($settlement);

            $provider->setArriereInitial($provider->getArriereInitial() - $amount);
            $fonctions->ecritureDeReglementsFournisseursDansLeJournalComptable($manager, $mode, $amount, $exercice, $date, $settlement, true);

            try{
              $manager->flush();
              $this->addFlash('success', 'Enregistrement de règlement des arriérés initiaux de <strong>'.$provider->getNom().'</strong> réussi.');
            } 
            catch(\Exception $e){
              $this->addFlash('danger', $e->getMessage());
            } 
            return $this->redirectToRoute('provider');
          }
          else{
            $this->addFlash('danger', 'Formulaire invalide, veuillez réessayer');
          }
        }
      }

      return $this->render('Admin/Provider/paiement-arriere-initial.html.twig', [
        'current'  => 'purchases',
        'provider' => $provider
      ]);
    }
}
