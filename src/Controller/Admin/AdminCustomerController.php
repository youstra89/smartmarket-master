<?php
// src/Controller/LuckyController.php
namespace App\Controller\Admin;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Customer;
use App\Form\CustomerType;
use App\Entity\Informations;
use App\Controller\FonctionsController;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

/**
 * @Route("/clients")
 */
class AdminCustomerController extends AbstractController
{
  /**
   * @Route("/", name="customer")
   * @IsGranted("ROLE_ADMIN")
   */
   public function index(EntityManagerInterface $manager)
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
    public function add(Request $request, EntityManagerInterface $manager, FonctionsController $fonctions)
    {
        $customer = new Customer();
        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);
        $last_customer = $manager->getRepository(Customer::class)->last_saved_customer();
        $reference = $fonctions->generateReference("customer", $last_customer);
        if($form->isSubmitted() && $form->isValid())
        {
          /** @var UploadedFile $imageFile */
          $imageFile = $form->get('photo')->getData();

          // this condition is needed because the 'brochure' field is not required
          // so the PDF file must be processed only when a file is uploaded
          if ($imageFile) {
              $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
              // this is needed to safely include the file name as part of the URL
              $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
              $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

              // Move the file to the directory where brochures are stored
              try {
                  $imageFile->move(
                      $this->getParameter('customers_images_directory'),
                      $newFilename
                  );
              } catch (FileException $e) {
                  // ... handle exception if something happens during file upload
              }

              // updates the 'brochureFilename' property to store the PDF file name
              // instead of its contents
              $customer->setPhoto($newFilename);
          }
          $customer->setCreatedBy($this->getUser());
          $manager->persist($customer);
          try{
            $manager->flush();
            $this->addFlash('success', 'Enregistrement du client <strong>'.$customer->getFirstname().' '.$customer->getLastname().'</strong> réussie.');
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
    public function edit(Request $request, EntityManagerInterface $manager, Customer $customer)
    {
        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
          /** @var UploadedFile $imageFile */
          $imageFile = $form->get('photo')->getData();

          // this condition is needed because the 'brochure' field is not required
          // so the PDF file must be processed only when a file is uploaded
          if ($imageFile) {
              $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
              // this is needed to safely include the file name as part of the URL
              $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
              $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

              // Move the file to the directory where brochures are stored
              try {
                  $imageFile->move(
                      $this->getParameter('customers_images_directory'),
                      $newFilename
                  );
              } catch (FileException $e) {
                  // ... handle exception if something happens during file upload
              }

              // updates the 'brochureFilename' property to store the PDF file name
              // instead of its contents
              $customer->setPhoto($newFilename);
          }
          $customer->setUpdatedAt(new \DateTime());
          $customer->setUpdatedBy($this->getUser());
          $manager->persist($customer);
          try{
            $manager->flush();
            $this->addFlash('success', 'Mise à jour du client <strong>'.$customer->getFirstname().' '.$customer->getLastname().'</strong> réussie.');
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          } 
          return $this->redirectToRoute('customer');
        }
        return $this->render('Admin/Customer/customer-edit.html.twig', [
          'current'  => 'sells',
          'customer' => $customer,
          'form'     => $form->createView()
        ]);
    }

    /**
     * @Route("/informations/{id}", name="customer_info")
     * @IsGranted("ROLE_ADMIN")
     * @param Customer $customer
     */
    public function info(Customer $customer)
    {
        return $this->render('Admin/Customer/customer-info.html.twig', [
          'current'  => 'sells',
          'customer' => $customer
        ]);
    }


    /**
     * @Route("/impression-liste-des-clients", name="impression_customers")
     */
    public function inventaire_de_stock_de_produits(EntityManagerInterface $manager)
    {
        $info = $manager->getRepository(Informations::class)->find(1);
        $customers = $manager->getRepository(Customer::class)->findBy(["is_deleted" => false]);
        if(empty($customers)){
            $this->addFlash('warning', "Aucun client enregistré pour le moment.");
            return $this->redirectToRoute('customer');
        }

        // Configure Dompdf according to your needs
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        
        // Instantiate Dompdf with our options
        $dompdf = new Dompdf($pdfOptions);
        
        // Retrieve the HTML generated in our twig file
        $html = $this->renderView('Admin/Customer/impression-liste-customers.html.twig', [
            'info'  => $info,
            'customers'  => $customers,
        ]);
        
        // Load HTML to Dompdf
        $dompdf->loadHtml($html);
        //"dompdf/dompdf": "^0.8.3",
        
        // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->setPaper('A4', 'landscape');

        // Render the HTML as PDF
        $dompdf->render();

        // Output the generated PDF to Browser (force download)
        $dompdf->stream("clients.pdf", [
            "Attachment" => false
        ]);
    }
}
