<?php
// src/Controller/LuckyController.php
namespace App\Controller\Admin;

use App\Entity\Provider;
use App\Form\ProviderType;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * @Route("/admin/fournisseurs")
 */
class AdminProviderController extends AbstractController
{
  /**
   * @Route("/", name="provider")
   * @IsGranted("ROLE_ADMIN")
   */
   public function index(ObjectManager $manager)
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
    public function add(Request $request, ObjectManager $manager)
    {
        $provider = new Provider();
        $form = $this->createForm(ProviderType::class, $provider);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $this->addFlash('success', 'Enregistrement du fournisseur <strong>'.$provider->getFirstname().' '.$provider->getLastname().'</strong> réussie.');
            $manager->persist($provider);
            $manager->flush();
            return $this->redirectToRoute('provider');
        }
        return $this->render('Admin/Provider/provider-add.html.twig', [
          'current' => 'purchases',
          'form'    => $form->createView()
        ]);
    }

    /**
     * @Route("/edit/{id}", name="provider.edit")
     * @IsGranted("ROLE_ADMIN")
     * @param Provider $provider
     */
    public function edit(Request $request, ObjectManager $manager, Provider $provider)
    {
        $form = $this->createForm(ProviderType::class, $provider);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $this->addFlash('success', 'Mise à jour de <strong>'.$provider->getCategory()->getName().' '.$provider->getMark()->getLabel().' - '.$provider->getDescription().'</strong> réussie.');
            $provider->setUpdatedAt(new \DateTime());
            $manager->persist($provider);
            $manager->flush();
            return $this->redirectToRoute('provider');
        }
        return $this->render('Admin/Provider/provider-edit.html.twig', [
          'current' => 'purchases',
          'provider' => $provider,
          'form'    => $form->createView()
        ]);
    }
}
