<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Product;
use App\Entity\Customer;
use App\Entity\Settlement;
use App\Entity\ComptaCompte;
use App\Entity\Informations;
use App\Entity\ComptaExercice;
use App\Entity\CustomerCommande;
use App\Entity\ComptaCompteExercice;
use App\Entity\CustomerCommandeSearch;
use App\Entity\CustomerCommandeDetails;
use App\Form\CustomerCommandeSearchType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\Collection;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Controller\FonctionsComptabiliteController;
use App\Entity\Avoir;
use NumberFormatter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/les-factures-avoir")
 */
class AdminAvoirController extends AbstractController
{
    /**
     * @Route("/", name="avoir")
     * @IsGranted("ROLE_VENTE")
     */
    public function index(Request $request, EntityManagerInterface $manager, FonctionsComptabiliteController $fonctions)
    {
      $exercice  = $fonctions->exercice_en_cours($manager);
      $exerciceId = $exercice->getId();
      $avoirs = $manager->getRepository(Avoir::class)->avoirDeLExercice($exerciceId);
      // dump($avoirs[0]);

      return $this->render('Avoir/index.html.twig', [
        'current' => 'avoir',
        'avoirs'   => $avoirs
      ]);
    }
}
