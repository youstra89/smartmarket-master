<?php
// src/Controller/LuckyController.php
namespace App\Controller\Admin;

use App\Entity\Echeance;
use App\Entity\Category;
use App\Form\CategoryType;
use App\Entity\CustomerCommande;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

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

}
