<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Acompte;
use App\Entity\Cloture;
use App\Entity\Activite;
use App\Entity\Customer;
use App\Entity\Settlement;
use App\Form\CustomerType;
use App\Entity\Informations;
use App\Entity\ComptaExercice;
use App\Entity\RetraitAcompte;
use App\Controller\FonctionsController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\FonctionsComptabiliteController;
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
      return $this->render('Customer/index.html.twig', [
        'current'   => 'sells',
        'customers' => $customers
      ]);
  }

    /**
     * @Route("/ajout", name="customer_add")
     * @IsGranted("ROLE_ADMIN")
     */
    public function add(Request $request, EntityManagerInterface $manager, FonctionsController $fonctions, FonctionsComptabiliteController $fonctionsComptables)
    {
        $customer = new Customer();
        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);
        $last_customer = $manager->getRepository(Customer::class)->last_saved_customer();
        $reference = $fonctions->generateReference("customer", $last_customer);
        if($form->isSubmitted() && $form->isValid())
        {
          $exercice  = $fonctionsComptables->exercice_en_cours($manager);

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
          $nom = $customer->getNom();
          $customer->setAcompte(0);
          $customer->setCreanceInitiale(0);
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
        return $this->render('Customer/customer-add.html.twig', [
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
        return $this->render('Customer/customer-edit.html.twig', [
          'current'  => 'sells',
          'customer' => $customer,
          'form'     => $form->createView()
        ]);
    }
    
    /**
     * @Route("/editer-acompte-client/{id}", name="editer_acompte_client")
     * @IsGranted("ROLE_SUPER_ADMIN")
     * @param Customer $customer
     */
    public function editer_acompte_client(Request $request, EntityManagerInterface $manager, Customer $customer, $id)
    {
      if($request->isMethod('post'))
      {
        $data = $request->request->all();
        $token = $data['token'];
        if($this->isCsrfTokenValid('editer_acompte_client', $token)){
          $montant = $data["montant"];
          if($montant == $customer->getAcompte()){
            $this->addFlash('warning', "Aucun changement observé.");  
          }
          else{
            $activite = new Activite();
            $activite->setTitre("Update Acompte");
            $activite->setDescription("Mise à jour de l'acompte du client ".$id." de ".$customer->getAcompte()." à ".$montant);
            $activite->setDate(new \DateTime());
            $activite->setUser($this->getUser());
            $manager->persist($activite);

            $customer->setAcompte($montant);
            $customer->setUpdatedAt(new \DateTime());
            $customer->setUpdatedBy($this->getUser());

            try{
              $manager->flush();
              $this->addFlash('success', 'Mise à jour de l\'acompte client <strong>'.$customer->getNom().'</strong> réussie.');
            } 
            catch(\Exception $e){
              $this->addFlash('danger', $e->getMessage());
            } 
          }
        }
        return $this->redirectToRoute('customer');
      }
      return $this->render('Customer/edit-customer-acompte.html.twig', [
        'current'  => 'sells',
        'customer' => $customer,
      ]);
    }

    /**
     * @Route("/informations/{id}", name="customer_info")
     * @IsGranted("ROLE_ADMIN")
     * @param Customer $customer
     */
    public function info(Customer $customer)
    {
        return $this->render('Customer/customer-info.html.twig', [
          'current'  => 'sells',
          'customer' => $customer
        ]);
    }

    /**
     * @Route("/retrait-d-argent-de-l-acompte-du-client/{id}", name="rembourser_avance_client", requirements={"id"="\d+"})
     *
     * @param Request $request
     * @param Customer $customer
     * @return void
     */
    public function rembourser_avance_client(Request $request, EntityManagerInterface $manager, Customer $customer, int $id, AdminSellController $adminSellController, FonctionsComptabiliteController $fonctions)
    {
      if(0 >= $customer->getAcompte()){
        $this->addFlash('danger', 'Acompte du client nul. Impossible de lui donner de l\'argent.');
        return $this->redirectToRoute('customer');  
      }

      if($request->isMethod('post'))
      {
        $data = $request->request->all();
        // return new Response(var_dump($data));
        if(!empty($data['token']))
        {
          $token = $data['token'];
          if($this->isCsrfTokenValid('token_retrait_acompte', $token)){
            $date   = new \DateTime($data['date']);
            $mode   = (int) $data['mode'];
            $amount = (int) $data['amount'];
            if($amount > $customer->getAcompte()){
              $this->addFlash('danger', 'Le montant saisi est supérieur au total de l\'acompte.');
              return $this->redirectToRoute('rembourser_avance_client', ["id" => $id]);  
            }
            $exercice  = $fonctions->exercice_en_cours($manager);
            $retrait = new RetraitAcompte();
            $retrait->setExercice($exercice);
            $retrait->setCustomer($customer);
            $retrait->setDate($date);
            $retrait->setModePaiement($mode);
            $retrait->setMontant($amount);
            $retrait->setCreatedBy($this->getUser());
            $manager->persist($retrait);

            $customer->setAcompte($customer->getAcompte() - $amount);
            // $fonctions->ecriture_de_retrait_acompte_client_dans_le_journal_comptable($manager, $mode, $amount, $exercice, $date, $retrait, true);

            try{
              $manager->flush();
              $this->addFlash('success', 'Enregistrement du retrait d\'argent de l\'avance versée par <strong>'.$customer->getNom().'</strong> réussi.');
            } 
            catch(\Exception $e){
              $this->addFlash('danger', $e->getMessage());
            } 
            return $this->redirectToRoute('customer');
          }
          else{
            $this->addFlash('danger', 'Formulaire invalide, veuillez réessayer');
          }
        }
      }

      return $this->render('Customer/retrait-acompte.html.twig', [
        'current'  => 'sells',
        'customer' => $customer
      ]);
    }


    /**
     * @Route("/modification-creances-et-acomptes-initiaux-client/{id}", name="ajouter_acompte_clients")
     */
    public function ajouter_acompte_clients(Request $request, EntityManagerInterface $manager, Customer $customer, int $id, FonctionsComptabiliteController $fonctions)
    {
      if($request->isMethod('post'))
      {
        $data = $request->request->all();
        // return new Response(var_dump($data));
        if(!empty($data['token']))
        {
          $token = $data['token'];
          if($this->isCsrfTokenValid('enregistrment_acompte_client', $token)){
            $date           = new \DateTime($data['date']);
            $montantAcompte = (int) $data['acompte'];
            $commentaire    = empty($data['comment']) ? null: $data['comment'];
            $exercice  = $fonctions->exercice_en_cours($manager);
            $cloture = $manager->getRepository(Cloture::class)->findOneByDate($date);
            if(!empty($cloture)){
              $this->addFlash('danger', 'Action non autorisée. Les activités du <strong>'.$date->format("d-m-Y").'</strong> ont déjà été clôturées.');
              return $this->redirectToRoute('ajouter_acompte_clients', ["id" => $id]);
            }
            if($montantAcompte > 0){
              $acompte = new Acompte();
              $acompte->setCustomer($customer);
              $acompte->setDate($date);
              $acompte->setMontant($montantAcompte);
              $acompte->setExercice($exercice);
              $acompte->setCommentaire($commentaire);
              $acompte->setCreatedBy($this->getUser());
              $manager->persist($acompte);
              $customer->setAcompte($customer->getAcompte() + $montantAcompte);
              try{
                $manager->flush();
                $this->addFlash('success', 'Enregistrement d\'acompte de <strong>'.number_format($montantAcompte, 0, ',', ' ').' F</strong> pour le client <strong>'.$customer->getNom().'</strong> réussi.');
              } 
              catch(\Exception $e){
                $this->addFlash('danger', $e->getMessage());
              } 
            }
            else{
              $this->addFlash('warning', "Montant saisi incorrect. Il ne doit pas être égal à 0");
            }
            return $this->redirectToRoute('customer');
          }
          else{
            $this->addFlash('danger', 'Formulaire invalide, veuillez réessayer');
          }
        }
      }

      return $this->render('Customer/customer-add-acompte.html.twig', [
          'current' => 'sells',
          'customer' => $customer,
        ]);
    }


    /**
     * @Route("/impression-de-ticket-acompte/{id}", name="ticket_acompte", requirements={"id"="\d+"})
     * @param Acompte $acompte
     * @IsGranted("ROLE_VENTE")
     */
    public function ticket_acompte(int $id, EntityManagerInterface $manager, Acompte $acompte)
    {
      $info = $manager->getRepository(Informations::class)->find(1);
      // Configure Dompdf according to your needs
      $pdfOptions = new Options();
      $pdfOptions->set('defaultFont', 'Arial');
      
      // Instantiate Dompdf with our options
      $dompdf = new Dompdf($pdfOptions);
      
      $html = $this->renderView('Customer/ticket-acompte.html.twig', [
          'info'    => $info,
          'acompte' => $acompte,
      ]);
      
      // Load HTML to Dompdf
      $dompdf->loadHtml($html);
      // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
      // $dompdf->setPaper('A8', 'portrait');
      $dompdf->setPaper('A8', 'landscape');

      $orientation = "landscape";
      // $nbr = count($commande->getProduct());
      // $nbr = $nbr * 25 + 350;
      $paper = [0, 0, 400, 240];
      // dd($paper);
      $dompdf->setPaper($paper, $orientation);

      // Render the HTML as PDF
      $dompdf->render();

      //File name
      $filename = "ticket-acompte-".$acompte->getCustomer()->getReference()."-".$acompte->getDate()->format("d-m-Y");

      // Output the generated PDF to Browser (force download)
      $dompdf->stream($filename.".pdf", [
          "Attachment" => false
      ]);
    }


    /**
     * @Route("/impression-de-recu-acompte/{id}", name="ticket_acompte_gf", requirements={"id"="\d+"})
     * @param Acompte $acompte
     * @IsGranted("ROLE_VENTE")
     */
    public function ticket_acompte_gf(int $id, EntityManagerInterface $manager, Acompte $acompte)
    {
      $info = $manager->getRepository(Informations::class)->find(1);
      // Configure Dompdf according to your needs
      $pdfOptions = new Options();
      $pdfOptions->set('defaultFont', 'Arial');
      
      // Instantiate Dompdf with our options
      $dompdf = new Dompdf($pdfOptions);
      
      $html = $this->renderView('Customer/recu-acompte.html.twig', [
        'info'    => $info,
        'acompte' => $acompte,
      ]);
      
      // Load HTML to Dompdf
      $dompdf->loadHtml($html);
      // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
      // $dompdf->setPaper('A8', 'portrait');
      $dompdf->setPaper('A8', 'landscape');
      $dompdf->setPaper('A4', 'portrait');

      // Render the HTML as PDF
      $dompdf->render();

      //File name
      $filename = "recu-acompte-".$acompte->getCustomer()->getReference()."-".$acompte->getDate()->format("d-m-Y");

      // Output the generated PDF to Browser (force download)
      $dompdf->stream($filename.".pdf", [
          "Attachment" => false
      ]);
    }


    /**
     * @Route("/impression-liste-des-clients", name="impression_customers")
     */
    public function impression_customers(EntityManagerInterface $manager)
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
        $html = $this->renderView('Customer/impression-liste-customers.html.twig', [
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
