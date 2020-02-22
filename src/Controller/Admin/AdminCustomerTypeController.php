<?php
// src/Controller/LuckyController.php
namespace App\Controller\Admin;

use App\Entity\CustomerType;
use App\Form\CustomerTypeType;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/admin/type-de-customers")
 */
class AdminCustomerTypeController extends AbstractController
{
    /**
     * @Route("/", name="customertypes")
     * @IsGranted("ROLE_COMPTABLE")
     */
    public function index(ObjectManager $manager)
    {
        $customertypes = $manager->getRepository(CustomerType::class)->findAll();
        return $this->render('Admin/CustomerType/index.html.twig', [
          'current' => 'accounting',
          'customertypes'   => $customertypes
        ]);
    }

    /**
     * @Route("/add", name="customertype.add")
     * @IsGranted("ROLE_COMPTABLE")
     */
    public function add(Request $request, ObjectManager $manager)
    {
        $customertype = new CustomerType();
        $form = $this->createForm(CustomerTypeType::class, $customertype);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
          $customertype->setCreatedAt(new \DateTime());
          $customertype->setCreatedBy($this->getUser());
          $manager->persist($customertype);
          try{
            $manager->flush();
            $this->addFlash('success', 'Enregistrement de la catégorie <strong>'.$customertype->getType().'</strong> réussie.');
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          } 
            return $this->redirectToRoute('customertypes');
        }
          
        return $this->render('Admin/CustomerType/customertype-add.html.twig', [
          'current' => 'accounting',
          'form'    => $form->createView()
        ]);
    }
          
    /**
     * @Route("/edit/{id}", name="customertype.edit")
     * @IsGranted("ROLE_COMPTABLE")
     * @param CustomerType $customertype
     */
    public function edit(Request $request, ObjectManager $manager, CustomerType $customertype)
    {
      $form = $this->createForm(CustomerTypeType::class, $customertype);
      $form->handleRequest($request);
      if($form->isSubmitted() && $form->isValid())
      {
        $customertype->setUpdatedAt(new \DateTime());
        $customertype->setUpdatedBy($this->getUser());
        try{
          $manager->flush();
          $this->addFlash('success', 'Mise à jour de la catégorie <strong>'.$customertype->getType().'</strong> réussie.');
        } 
        catch(\Exception $e){
          $this->addFlash('danger', $e->getMessage());
        } 
        return $this->redirectToRoute('customertypes');
      }

      return $this->render('Admin/CustomerType/customertype-edit.html.twig', [
        'current'  => 'accounting',
        'customertype' => $customertype,
        'form'     => $form->createView()
      ]);
    }
}
