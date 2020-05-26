<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Activite;
use App\Entity\Customer;
use App\Entity\Settlement;
use App\Form\CustomerType;
use App\Entity\Informations;
use App\Entity\ComptaExercice;
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
     * @Route("/add", name="customer.add")
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
          $exercice  = $manager->getRepository(ComptaExercice::class)->dernierExerciceEnCours();

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
          $acompte = $customer->getAcompte();
          $creance = $customer->getCreanceInitiale();
          if($acompte < 0 or $creance < 0){
            $this->addFlash('danger', "Les valeurs de <strong>Avances</strong> et / ou <strong>Créance</strong> doivent être supérieur à zéro");
            return $this->redirectToRoute('customer.add');
          }
          // Lors de l'enregistrement d'un nouveau nouveau client, s'il y a des avances et ou des créances, il faut les ajouter aux ecritures comptables
          if($acompte > 0){
            $fonctionsComptables->ecriture_des_avances_ou_des_creances_initiales($manager, $acompte, $exercice, new \DateTime(), $nom, true, "client");
          }
          if($creance > 0){
            $fonctionsComptables->ecriture_des_avances_ou_des_creances_initiales($manager, $creance, $exercice, new \DateTime(), $nom, false, "client");
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
     * @Route("/enregistrement-creance-initiale/{id}", name="paiement_creance_initiale", requirements={"id"="\d+"})
     *
     * @param Request $request
     * @param Customer $customer
     * @return void
     */
    public function paiement_creance_initiale(Request $request, EntityManagerInterface $manager, Customer $customer, int $id, AdminSellController $adminSellController, FonctionsComptabiliteController $fonctions)
    {
      if(0 > $customer->getCreanceInitiale() or null == $customer->getCreanceInitiale()){
        $this->addFlash('danger', 'Aucune créance initiale à payer pour ce client');
        return $this->redirectToRoute('customer');  
      }

      if($request->isMethod('post'))
      {
        $data = $request->request->all();
        // return new Response(var_dump($data));
        if(!empty($data['token']))
        {
          $token = $data['token'];
          if($this->isCsrfTokenValid('token_reglement_creance_initiale', $token)){
            $date   = new \DateTime($data['date']);
            $mode   = (int) $data['mode'];
            $amount = (int) $data['amount'];
            if($amount > $customer->getCreanceInitiale()){
              $this->addFlash('danger', 'Le montant saisie est supérieur au total de la créance initiale');
              return $this->redirectToRoute('paiement_creance_initiale', ["id" => $id]);  
            }
            $exercice  = $manager->getRepository(ComptaExercice::class)->dernierExerciceEnCours();
            $user = $this->getUser();
            $reference = $adminSellController->generateInvoiceReference($manager);
            $settlement = new Settlement();
            $settlement->setDate($date);
            $settlement->setReference($reference);
            $settlement->setModePaiement($mode);
            $settlement->setAmount($amount);
            $settlement->setNumber(0);
            $settlement->setReceiver($user);
            $settlement->setCreatedBy($this->getUser());
            $manager->persist($settlement);

            $customer->setCreanceInitiale($customer->getCreanceInitiale() - $amount);
            $fonctions->ecriture_de_reglements_clients_dans_le_journal_comptable($manager, $mode, $amount, $exercice, $date, $settlement, true);

            try{
              $manager->flush();
              $this->addFlash('success', 'Enregistrement de règlement de créance initiale de <strong>'.$customer->getNom().'</strong> réussi.');
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

      return $this->render('Customer/paiement-creance-initiale.html.twig', [
        'current'  => 'sells',
        'customer' => $customer
      ]);
    }


    /**
     * @Route("/modification-creances-et-acomptes-initiaux-client/{id}", name="update_customer_initial_solde")
     */
    public function update_customer_initial_solde(Request $request, EntityManagerInterface $manager, Customer $customer, int $id, FonctionsComptabiliteController $fonctions)
    {
      if($request->isMethod('post'))
      {
        $data = $request->request->all();
        // return new Response(var_dump($data));
        if(!empty($data['token']))
        {
          $token = $data['token'];
          if($this->isCsrfTokenValid('token_definition_creance_initiale', $token)){
            $acompte = (int) $data['acompte'];
            $creance = (int) $data['creance'];
            $nom = $customer->getNom();
            $exercice  = $manager->getRepository(ComptaExercice::class)->dernierExerciceEnCours();
            $activite = new Activite();
            $activite->setDate(new \DateTime());
            $activite->setUser($this->getUser());
            $activite->setDescription("Mise à jour de créances et/ou avance initiales du client ".$nom);
            $manager->persist($activite);
            if($creance > 0){
              $fonctions->ecriture_de_la_mise_a_jour_des_avances_ou_des_creances_initiales($manager, $customer->getCreanceInitiale(), $creance, $exercice, new \DateTime(), $nom, "creance", "client");
            }
            if($acompte > 0){
              $fonctions->ecriture_de_la_mise_a_jour_des_avances_ou_des_creances_initiales($manager, $customer->getAcompte(), $acompte, $exercice, new \DateTime(), $nom, "acompte", "client");
            }
            $customer->setAcompte($acompte);
            $customer->setCreanceInitiale($creance);
            // dd($res);
            try{
              $manager->flush();
              $this->addFlash('success', 'Mise à jour des créances initialessells de <strong>'.$customer->getNom().'</strong> réussi.');
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

      return $this->render('Customer/update-customer-initial-solde.html.twig', [
          'current' => 'sells',
          'customer' => $customer,
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
