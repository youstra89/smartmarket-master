<?php
// src/Controller/LuckyController.php
namespace App\Controller\Admin;

use App\Entity\TypeDepense;
use App\Form\TypeDepenseType;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/admin/type-de-depenses")
 */
class AdminTypeDepenseController extends AbstractController
{
    /**
     * @Route("/", name="typedepenses")
     * @IsGranted("ROLE_COMPTABLE")
     */
    public function index(ObjectManager $manager)
    {
        $typedepenses = $manager->getRepository(TypeDepense::class)->findAll();
        return $this->render('Admin/TypeDepense/index.html.twig', [
          'current' => 'accounting',
          'typedepenses'   => $typedepenses
        ]);
    }

    /**
     * @Route("/add", name="typedepense.add")
     * @IsGranted("ROLE_COMPTABLE")
     */
    public function add(Request $request, ObjectManager $manager)
    {
        $typedepense = new TypeDepense();
        $form = $this->createForm(TypeDepenseType::class, $typedepense);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
          $typedepense->setCreatedAt(new \DateTime());
          $typedepense->setCreatedBy($this->getUser());
          $manager->persist($typedepense);
          try{
            $manager->flush();
            $this->addFlash('success', 'Enregistrement de la catégorie <strong>'.$typedepense->getLabel().'</strong> réussie.');
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          } 
            return $this->redirectToRoute('typedepenses');
        }
          
        return $this->render('Admin/TypeDepense/typedepense-add.html.twig', [
          'current' => 'accounting',
          'form'    => $form->createView()
        ]);
    }
          
    /**
     * @Route("/edit/{id}", name="typedepense.edit")
     * @IsGranted("ROLE_COMPTABLE")
     * @param TypeDepense $typedepense
     */
    public function edit(Request $request, ObjectManager $manager, TypeDepense $typedepense)
    {
      $form = $this->createForm(TypeDepenseType::class, $typedepense);
      $form->handleRequest($request);
      if($form->isSubmitted() && $form->isValid())
      {
        $typedepense->setUpdatedAt(new \DateTime());
        $typedepense->setUpdatedBy($this->getUser());
        try{
          $manager->flush();
          $this->addFlash('success', 'Mise à jour de la catégorie <strong>'.$typedepense->getLabel().'</strong> réussie.');
        } 
        catch(\Exception $e){
          $this->addFlash('danger', $e->getMessage());
        } 
        return $this->redirectToRoute('typedepenses');
      }

      return $this->render('Admin/TypeDepense/typedepense-edit.html.twig', [
        'current'  => 'accounting',
        'typedepense' => $typedepense,
        'form'     => $form->createView()
      ]);
    }
}
