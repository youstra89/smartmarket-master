<?php
// src/Controller/LuckyController.php
namespace App\Controller\Admin;

use App\Entity\Country;
use App\Form\CountryType;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/admin/countries")
 */
class AdminCountryController extends AbstractController
{
    /**
     * @Route("/", name="country")
     * @IsGranted("ROLE_ADMIN")
     */
    public function index(ObjectManager $manager)
    {
        $countries = $manager->getRepository(Country::class)->findAll();
        return $this->render('Admin/Country/index.html.twig', [
          'current'    => 'purchases',
          'countries' => $countries
        ]);
    }

    /**
     * @Route("/add", name="country.add")
     * @IsGranted("ROLE_ADMIN")
     */
    public function add(Request $request, ObjectManager $manager)
    {
        $country = new Country();
        $form = $this->createForm(CountryType::class, $country);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
          // dd($country);
          $country->setCreatedBy($this->getUser());
          $manager->persist($country);
          try{
            $manager->flush();
            $this->addFlash('success', 'Enregistrement de la catégorie <strong>'.$country->getName().'</strong> réussie.');
          } 
          catch(\Exception $e){
            $this->addFlash('danger', $e->getMessage());
          } 
          return $this->redirectToRoute('country');
        }
        // else{
        //   $this->addFlash('danger', 'Enregistrement de la catégorie réussie.');
        // }
          
        return $this->render('Admin/Country/country-add.html.twig', [
          'current' => 'purchases',
          'form'    => $form->createView()
        ]);
    }
          
    /**
     * @Route("/edit/{id}", name="country.edit")
     * @IsGranted("ROLE_ADMIN")
     * @param Country $country
     */
    public function edit(Request $request, ObjectManager $manager, Country $country)
    {
      $form = $this->createForm(CountryType::class, $country);
      $form->handleRequest($request);
      if($form->isSubmitted() && $form->isValid())
      {
        $country->setUpdatedAt(new \DateTime());
        $country->setUpdatedBy($this->getUser());
        try{
          $manager->flush();
          $this->addFlash('success', 'Mise à jour de la catégorie <strong>'.$country->getName().'</strong> réussie.');
        } 
        catch(\Exception $e){
          $this->addFlash('danger', $e->getMessage());
        } 
        return $this->redirectToRoute('country');
      }

      return $this->render('Admin/Country/country-edit.html.twig', [
        'current'  => 'purchases',
        'country'  => $country,
        'form'     => $form->createView()
      ]);
    }
}
