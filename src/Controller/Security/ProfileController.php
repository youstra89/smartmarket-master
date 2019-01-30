<?php
// src/Controller/LuckyController.php
namespace App\Controller\Security;

use App\Entity\ChangePWD;
use App\Form\ChangePWDType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/profile")
 * @Security("has_role('ROLE_USER')")
 */
class ProfileController extends AbstractController
{
  /**
   * @Route("/", name="profile")
   */
    public function index()
    {
      // $em = $this->getDoctrine()->getManager();
      // $mosquees = $em->getRepository(User::class)->findAll();
      $user = $this->getUser();
      // dump($user);
      return $this->render('Profil/index.html.twig', [
        'user' => $user
      ]);
    }

  /**
   * @Route("/changer-le-mot-de-passe", name="change_pwd")
   */
    public function changer_pwd(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
      $user = $this->getUser();
      $pwd = new ChangePWD();
      // $pwd->setPwd();
      $form = $this->createForm(ChangePWDType::class, $pwd);
      $form->handleRequest($request);

      if($form->isSubmitted() && $form->isValid())
      {
        /**
         * Pour la changement de mot de passe, il y a plusieurs vérifications à faire.
         * D'abord les champs ne doivent pas être vides
         * On compare ensuite la valeur de l'ancien pwd saisi à ce qui est en db
         *    s'ils sont identique, on continue, sinon on arrête
         * Lorsqu'ils sont identiques, on compare le nouveau pwd et la confirm du
         * nouveau pwd.
         *    s'ils sont identiques, on continue, sinon on arrête
         */
        // $em->flush();

        // return new Response(var_dump($tab));

        if(!$passwordEncoder->isPasswordValid($user, $pwd->getPassword()))
        {
          $this->addFlash('error', 'L\'ancien mot de passe que vous avez saisi n\'est pas correct.');
          return $this->redirectToRoute('change_pwd');
        }
        else{
          // return new Response(var_dump($form));
          if($pwd->getNewPassword() != $pwd->getNewPassword1())
          {
            $this->addFlash('error', 'La confirmation du nouveau mot de passe a échoué. Les mots de passe saisis ne sont pas identiques.');
            return $this->redirectToRoute('change_pwd');
          }
          else{
            $em = $this->getDoctrine()->getManager();
            $password = $passwordEncoder->encodePassword($user, $pwd->getNewPassword());
            $user->setPassword($password);
            $user->setPwdChangedAt(new \DateTime());
            $this->addFlash('success', 'Mot de passe modifié avec succès. Veuillez vous reconnecter.');
            $em->flush();
            return $this->redirectToRoute('profile');
          }
        }
      }
      return $this->render('Profil/changer-mot-de-passe.html.twig', [
        'user' => $user,
        'form' => $form->createView()
      ]);
    }
}
