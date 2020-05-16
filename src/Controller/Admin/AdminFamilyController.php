<?php
// src/Controller/LuckyController.php
namespace App\Controller\Admin;

use App\Entity\Family;
use App\Form\FamilyType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/admin/families")
 */
class AdminFamilyController extends AbstractController
{
    /**
     * @Route("/", name="family")
     * @IsGranted("ROLE_ADMIN")
     */
    public function index(EntityManagerInterface $manager)
    {
        $families = $manager->getRepository(Family::class)->findAll();
        return $this->render('Admin/Family/index.html.twig', [
          'current' => 'products',
          'families'   => $families
        ]);
    }

    /**
     * @Route("/add", name="family.add")
     * @IsGranted("ROLE_ADMIN")
     */
    public function add(Request $request, EntityManagerInterface $manager)
    {
      $family = new Family();
      $form = $this->createForm(FamilyType::class, $family);
      $form->handleRequest($request);
      if($form->isSubmitted() && $form->isValid())
      {
        $family->setCreatedBy($this->getUser());
        $manager->persist($family);
        try{
          $manager->flush();
          $this->addFlash('success', 'Enregistrement de la catégorie <strong>'.$family->getLabel().'</strong> réussie.');
        } 
        catch(\Exception $e){
          $this->addFlash('danger', $e->getMessage());
        } 
          return $this->redirectToRoute('family');
      }
        
      return $this->render('Admin/Family/family-add.html.twig', [
        'current' => 'products',
        'form'    => $form->createView()
      ]);
    }
          
    /**
     * @Route("/edit/{id}", name="family.edit")
     * @IsGranted("ROLE_ADMIN")
     * @param Family $family
     */
    public function edit(Request $request, EntityManagerInterface $manager, Family $family)
    {
      $form = $this->createForm(FamilyType::class, $family);
      $form->handleRequest($request);
      if($form->isSubmitted() && $form->isValid())
      {
        $family->setUpdatedAt(new \DateTime());
        $family->setUpdatedBy($this->getUser());
        try{
          $manager->flush();
          $this->addFlash('success', 'Mise à jour de la catégorie <strong>'.$family->getLabel().'</strong> réussie.');
        } 
        catch(\Exception $e){
          $this->addFlash('danger', $e->getMessage());
        } 
        return $this->redirectToRoute('family');
      }

      return $this->render('Admin/Family/family-edit.html.twig', [
        'current'  => 'products',
        'family' => $family,
        'form'     => $form->createView()
      ]);
    }
}
