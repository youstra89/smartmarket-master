<?php
// src/Controller/LuckyController.php
namespace App\Controller\Admin;

use App\Entity\Informations;
use App\Form\InformationsType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Response;

// Include Dompdf required namespaces
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

/**
 * @Route("/admin/personal-informations")
 */
class AdminInformationsController extends AbstractController
{
    /**
     * @Route("/", name="informations")
     * @IsGranted("ROLE_SUPER_ADMIN")
     */
    public function index(ObjectManager $manager)
    {
      $info = $manager->getRepository(Informations::class)->find(1);

      return $this->render('Admin/Informations/index.html.twig', [
        'current'  => 'info',
        'info' => $info
      ]);
    }

    /**
     * @Route("/add", name="add_informations")
     * @IsGranted("ROLE_SUPER_ADMIN")
     */
    public function add(Request $request, ObjectManager $manager)
    {
        $info = new Informations();
        $form = $this->createForm(InformationsType::class, $info);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
          
          /** @var UploadedFile $imageFile */
          $imageFile = $form->get('logo')->getData();

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
                      $this->getParameter('images_directory'),
                      $newFilename
                  );
              } catch (FileException $e) {
                  // ... handle exception if something happens during file upload
              }

              // updates the 'brochureFilename' property to store the PDF file name
              // instead of its contents
              $info->setLogo($newFilename);
          }

          $manager->persist($info);
          try{
            $manager->flush();
            $this->addFlash('success', 'Enregistrement des informations réussie.');
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          } 
          return $this->redirectToRoute('informations');
        }
        return $this->render('Admin/Informations/add.html.twig', [
          'current' => 'info',
          'form'    => $form->createView()
        ]);
    }

    /**
     * @Route("/edit", name="edit_informations")
     * @IsGranted("ROLE_SUPER_ADMIN")
     */
    public function edit(Request $request, ObjectManager $manager)
    {
        $info = $manager->getRepository(Informations::class)->find(1);
        $form = $this->createForm(InformationsType::class, $info);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
          try{
            $manager->flush();
            $this->addFlash('success', 'Mise à jour des informations réussie.');
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          } 
          return $this->redirectToRoute('informations');
        }
        return $this->render('Admin/Informations/edit.html.twig', [
          'current' => 'info',
          'info' => $info,
          'form'    => $form->createView()
        ]);
    }
}
