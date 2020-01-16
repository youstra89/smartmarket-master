<?php
// src/Controller/LuckyController.php
namespace App\Controller\Admin;

use App\Entity\Customer;
use App\Form\CustomerType;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

use App\Controller\FonctionsController;

/**
 * @Route("/clients")
 */
class AdminCustomerController extends AbstractController
{
  /**
   * @Route("/", name="customer")
   * @IsGranted("ROLE_USER")
   */
   public function index(ObjectManager $manager)
   {
       $customers = $manager->getRepository(Customer::class)->findAll();
       return $this->render('Admin/Customer/index.html.twig', [
         'current'   => 'sells',
         'customers' => $customers
       ]);
   }

    /**
     * @Route("/add", name="customer.add")
     * @IsGranted("ROLE_USER")
     */
    public function add(Request $request, ObjectManager $manager, FonctionsController $fonctions)
    {
        $customer = new Customer();
        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);
        $last_customer = $manager->getRepository(Customer::class)->last_saved_customer();
        $reference = $fonctions->generateReference("customer", $last_customer);
        if($form->isSubmitted() && $form->isValid())
        {
          $customer->setCreatedBy($this->getUser());
          $manager->persist($customer);
          try{
            $manager->flush();
            $this->addFlash('success', 'Enregistrement du fournisseur <strong>'.$customer->getFirstname().' '.$customer->getLastname().'</strong> réussie.');
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          } 
          return $this->redirectToRoute('customer');
        }
        return $this->render('Admin/Customer/customer-add.html.twig', [
          'current' => 'sells',
          'form'    => $form->createView(),
          'reference' => $reference,
        ]);
    }

    /**
     * @Route("/edit/{id}", name="customer.edit")
     * @IsGranted("ROLE_ADMIN")
     * @param Customer $customer
     */
    public function edit(Request $request, ObjectManager $manager, Customer $customer)
    {
        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
          $customer->setUpdatedAt(new \DateTime());
          $customer->setUpdatedBy($this->getUser());
          $manager->persist($customer);
          try{
            $manager->flush();
            $this->addFlash('success', 'Mise à jour de <strong>'.$customer->getFirstname().' '.$customer->getLastname().'</strong> réussie.');
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          } 
          return $this->redirectToRoute('customer');
        }
        return $this->render('Admin/Customer/customer-edit.html.twig', [
          'current' => 'sells',
          'customer' => $customer,
          'form'    => $form->createView()
        ]);
    }
}
