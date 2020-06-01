<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Stock;
use App\Entity\Store;
use App\Entity\Product;
use App\Entity\Informations;
use App\Entity\ProviderCommande;
use App\Entity\Approvisionnement;
use App\Entity\DetailsApprovisionnement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/admin/statistiques")
 * @IsGranted("ROLE_STATISTIQUES")
 */
class StatistiquesController extends AbstractController
{
    /**
     * @Route("/", name="statistiques")
     */
    public function index(EntityManagerInterface $manager)
    {
        // $approvisionnements = $manager->getRepository(Approvisionnement::class)->findAll();
        return $this->render('Statistiques/index.html.twig', [
          'current' => 'statistiques',
        ]);
    }
}
