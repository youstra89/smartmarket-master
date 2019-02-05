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

/**
 * @Route("/admin/clients")
 */
class AdminCustomerController extends AbstractController
{
  /**
   * @Route("/", name="customer")
   * @IsGranted("ROLE_ADMIN")
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
     * @IsGranted("ROLE_ADMIN")
     */
    public function add(Request $request, ObjectManager $manager)
    {
        $customer = new Customer();
        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $this->addFlash('success', 'Enregistrement du fournisseur <strong>'.$customer->getFirstname().' '.$customer->getLastname().'</strong> réussie.');
            $manager->persist($customer);
            $manager->flush();
            return $this->redirectToRoute('customer');
        }
        return $this->render('Admin/Customer/customer-add.html.twig', [
          'current' => 'sells',
          'form'    => $form->createView()
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
            $this->addFlash('success', 'Mise à jour de <strong>'.$customer->getCategory()->getName().' '.$customer->getMark()->getLabel().' - '.$customer->getDescription().'</strong> réussie.');
            $customer->setUpdatedAt(new \DateTime());
            $manager->persist($customer);
            $manager->flush();
            return $this->redirectToRoute('customer');
        }
        return $this->render('Admin/Customer/customer-edit.html.twig', [
          'current' => 'sells',
          'customer' => $customer,
          'form'    => $form->createView()
        ]);
    }
}
