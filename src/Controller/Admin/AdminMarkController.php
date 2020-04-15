<?php
// src/Controller/LuckyController.php
namespace App\Controller\Admin;

use App\Entity\Mark;
use App\Form\MarkType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/admin/marks")
 */
class AdminMarkController extends AbstractController
{
    /**
     * @Route("/", name="mark")
     * @IsGranted("ROLE_ADMIN")
     */
    public function index(EntityManagerInterface $manager)
    {
        $marks = $manager->getRepository(Mark::class)->findAll();
        return $this->render('Admin/Mark/index.html.twig', [
          'current' => 'products',
          'marks'   => $marks
        ]);
    }

    /**
     * @Route("/add", name="mark.add")
     * @IsGranted("ROLE_ADMIN")
     */
    public function add(Request $request, EntityManagerInterface $manager)
    {
        $mark = new Mark();
        $form = $this->createForm(MarkType::class, $mark);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
          $mark->setCreatedBy($this->getUser());
          $manager->persist($mark);
          try{
            $manager->flush();
            $this->addFlash('success', 'Enregistrement de la catégorie <strong>'.$mark->getLabel().'</strong> réussie.');
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          } 
            return $this->redirectToRoute('mark');
          }
          
          return $this->render('Admin/Mark/mark-add.html.twig', [
            'current' => 'products',
            'form'    => $form->createView()
            ]);
          }
          
          /**
     * @Route("/edit/{id}", name="mark.edit")
     * @IsGranted("ROLE_ADMIN")
     * @param Mark $mark
     */
    public function edit(Request $request, EntityManagerInterface $manager, Mark $mark)
    {
      $form = $this->createForm(MarkType::class, $mark);
      $form->handleRequest($request);
      if($form->isSubmitted() && $form->isValid())
      {
        $mark->setUpdatedAt(new \DateTime());
        $mark->setUpdatedBy($this->getUser());
        try{
          $manager->flush();
          $this->addFlash('success', 'Mise à jour de la catégorie <strong>'.$mark->getLabel().'</strong> réussie.');
        } 
        catch(\Exception $e){
          $this->addFlash('danger', $e->getMessage());
        } 
        return $this->redirectToRoute('mark');
      }

      return $this->render('Admin/Mark/mark-edit.html.twig', [
        'current'  => 'products',
        'mark' => $mark,
        'form'     => $form->createView()
      ]);
    }
}
