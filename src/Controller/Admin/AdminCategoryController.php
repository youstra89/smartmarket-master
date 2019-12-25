<?php
// src/Controller/LuckyController.php
namespace App\Controller\Admin;

use App\Entity\Category;
use App\Form\CategoryType;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * @Route("/admin/categories")
 */
class AdminCategoryController extends AbstractController
{
    /**
     * @Route("/", name="category")
     * @IsGranted("ROLE_ADMIN")
     */
    public function index(ObjectManager $manager)
    {
        $categories = $manager->getRepository(Category::class)->findAll();
        return $this->render('Admin/Category/index.html.twig', [
          'current'    => 'products',
          'categories' => $categories
        ]);
    }

    /**
     * @Route("/add", name="category.add")
     * @IsGranted("ROLE_ADMIN")
     */
    public function add(Request $request, ObjectManager $manager)
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
          $category->setCreatedBy($this->getUser());
          $manager->persist($category);
          try{
            $manager->flush();
            $this->addFlash('success', 'Enregistrement de la catégorie <strong>'.$category->getName().'</strong> réussie.');
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          } 
            return $this->redirectToRoute('category');
          }
          
          return $this->render('Admin/Category/category-add.html.twig', [
            'current' => 'products',
            'form'    => $form->createView()
            ]);
          }
          
          /**
     * @Route("/edit/{id}", name="category.edit")
     * @IsGranted("ROLE_ADMIN")
     * @param Category $category
     */
    public function edit(Request $request, ObjectManager $manager, Category $category)
    {
      $form = $this->createForm(CategoryType::class, $category);
      $form->handleRequest($request);
      if($form->isSubmitted() && $form->isValid())
      {
        $category->setUpdatedAt(new \DateTime());
        $category->setUpdatedBy($this->getUser());
        try{
          $manager->flush();
          $this->addFlash('success', 'Mise à jour de la catégorie <strong>'.$category->getName().'</strong> réussie.');
        } 
        catch(\Exception $e){
          $this->addFlash('danger', $e->getMessage());
        } 
        return $this->redirectToRoute('category');
      }

      return $this->render('Admin/Category/category-edit.html.twig', [
        'current'  => 'products',
        'category' => $category,
        'form'     => $form->createView()
      ]);
    }
}
