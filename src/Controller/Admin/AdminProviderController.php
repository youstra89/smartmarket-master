<?php
// src/Controller/LuckyController.php
namespace App\Controller\Admin;

use App\Entity\Provider;
use App\Form\ProviderType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Controller\FonctionsController;

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
    public function add(Request $request, EntityManagerInterface $manager, FonctionsController $fonctions)
    {
        $provider = new Provider();
        $form = $this->createForm(ProviderType::class, $provider);
        $form->handleRequest($request);
        $last_provider = $manager->getRepository(Provider::class)->last_saved_provider();
        $reference = $fonctions->generateReference("provider", $last_provider);
        if($form->isSubmitted() && $form->isValid())
        {
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
}
