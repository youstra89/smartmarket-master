<?php
// src/Controller/LuckyController.php
namespace App\Controller\Security;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/gestion-utilisateurs")
 * @Security("has_role('ROLE_SUPER_ADMIN')")
 */
class UserManagementController extends AbstractController
{
  /**
   * @Route("/", name="users", requirements={"userId" = "\d+"})
   */
    public function index(Request $request, ObjectManager $manager)
    {
      $repoUser = $manager->getRepository(User::class);
      $users = $repoUser->findAll();

      return $this->render('UserManagement/index.html.twig', [
        'users'   => $users,
        'current' => 'users',
      ]);
    }


  /**
   * @Route("/gestion-roles-utilisateur/{id}", name="user_roles", methods="GET|POST", requirements={"id" = "\d+"})
   * @param User $user
   */
  public function gestion_des_roles_utilisateurs(Request $request, ObjectManager $manager, int $id, User $user)
  {
    if ($request->isMethod('post')) {
      $token = $request->get('_csrf_token');
      if($this->isCsrfTokenValid('roles_management', $token))
      {
        $data  = $request->request->all();
        if(empty($data["roles"]))
          $roles[] = "ROLE_USER";
        else
          $roles = $data["roles"];
        // return new Response(var_dump($roles));
        $user->setRoles($roles);
        $user->setUpdatedAt(new \DateTime());
        try{
          $manager->flush();
          $this->addFlash('success', 'Les rôles de l\'utilisateur <strong>'.$user->getUsername().'</strong> ont été mise à jour avec succès.');
        } 
        catch(\Exception $e){
          $this->addFlash('danger', $e->getMessage());
        } 

        return $this->redirectToRoute('users');
      }
    }

    return $this->render('UserManagement/gestion-roles-utilisateur.html.twig', [
      'user' => $user,
      'current' => 'users',
    ]);
  }

  /**
   * @Route("/edition-utilisateur/{id}", name="edit_user", methods="GET|POST", requirements={"id" = "\d+"})
   * @param User $user
   */
  public function editer_utilisateur(Request $request, ObjectManager $manager, int $id, User $user)
  {
    $form = $this->createForm(UserType::class, $user);

    $form->handleRequest($request);
    // if ($form->isSubmitted() && $form->isValid() && $this->isCsrfTokenValid('registration', $request->get('_csrf_token'))) {
    if ($form->isSubmitted() && $form->isValid()) {
      // $form->bind($request);
      $token = $request->get('_csrf_token');
      if($this->isCsrfTokenValid('edit_user', $token))
      {
        $data = $request->request->all();
        $date = $data['date'];
        if($date != $user->getBirthday())
          $user->setBirthday(new \DateTime($date));
        $user->setUpdatedAt(new \DateTime());
        try{
          $manager->flush();
          $this->addFlash('success', 'Edition de l\'utilisateur <strong>'.$user->getUsername().'</strong> terminée avec succès.');
        } 
        catch(\Exception $e){
          $this->addFlash('danger', $e->getMessage());
        }
        return $this->redirectToRoute('users');
      }
    }

    return $this->render('UserManagement/editer-utilisateur.html.twig', [
      'user' => $user,
      'current' => 'users',
      'form' => $form->createView(),
    ]);
  }

  /**
   * @Route("/supprimer-utilisateur/{userId}", name="delete_user", methods="GET|POST", requirements={"userId"="\d+"})
   */
  public function supprimer_utilisateur(Request $request, ObjectManager $manager, int $id, int $userId)
  {
    $repoUser = $manager->getRepository(User::class);
    $user = $repoUser->find($userId);
    $token = $request->get('_csrf_token');
    if($this->isCsrfTokenValid('delete_user', $token))
    {
      $user->setIsDeleted(true);
      $user->setDeletedAt(new \DateTime());
      // Si le $user est un enseignant, il va falloir supprimer également
      if($user->getIsEnseignant() == true)
      {
        $repoEnseignant = $manager->getRepository(Enseignant::class);
        $enseignant = $repoEnseignant->findOneBy(["user" => $userId]);
        $enseignant->setIsDeleted(true);
        $enseignant->setDeletedBy($this->getUser());
        $enseignant->setDeletedAt(new \DateTime());
      }
      try{
        $manager->flush();
        $this->addFlash('success', 'L\'utilisateur <strong>'.$user->getUsername().'</strong> supprimée avec succès.');
      }  
      catch(\Exception $e){
        $this->addFlash('danger', $e->getMessage());
      }
      return $this->redirectToRoute('users');
    }
  }

  /**
   * @Route("/informations-utilisateur/{id}", name="user_info", requirements={"id" = "\d+"})
   * @param User $user
   */
  public function user_informations(ObjectManager $manager, User $user)
  {
      return $this->render('UserManagement/informations-utilisateur.html.twig', [
        'user' => $user,
        'current' => 'users',
      ]);
  }

}
