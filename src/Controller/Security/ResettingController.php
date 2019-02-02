<?php

namespace App\Controller\Security;

use App\Entity\User;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class ResettingController extends AbstractController
{
    /**
     * @Route("/resetting", name="resetting")
     */
    public function resetting(Request $request, ObjectManager $em, \Swift_Mailer $mailer)
    {
      $form = $this->createFormBuilder()
        ->add('email', EmailType::class, ['required' => true, 'label' => 'Entrer votre email'])
        ->getForm();
      // dump($form->createView());
      $form->handleRequest($request);
      if ($form->isSubmitted() && $form->isValid()) {
        $token = $request->get('_csrf_token');
        if($this->isCsrfTokenValid('resetting', $token))
        {
          $data = $form->getData();
          $user = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
          if ($user === null) {
              $this->addFlash('danger', 'L\'adresse email saisie n\'est pas correcte.');
              return $this->redirectToRoute('resetting');
          }

          $user->setToken($token);
          $user->setPasswordRequestedAt(new \DateTime());
          $user->setDisabled(true);
          $em->flush();

          $url = $this->generateUrl('resetting_compte', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

          $message = (new \Swift_Message('Réinitialisation du mot de passe de votre compte Orientation Authentique'))
          ->setFrom('orientationauthentique@gmail.com')
          ->setTo($user->getEmail())
          ->setBody(
            $this->renderView(
              // templates/emails/registration.html.twig
              'Emails/resetting.html.twig', [
                'url'  => $url,
                'user' => $user
              ]
            ),
            'text/html'
          );
          $mailer->send($message);

          $this->addFlash('success', 'Un mail de réinitialisation vous a été envoyé à l\'adresse saisie. Veuillez vérifier votre boîte de réception.');
          return $this->render(
              'Security/reinitialisation.html.twig',[
                'user' => $user
              ]
          );
        }
      }

      return $this->render(
          'Security/resetting.html.twig', [
            'form' => $form->createView(),
          ]
      );
    }


    /**
     * @Route("reinitialisation-de-compte-orientation-authetique/{token}", name="resetting_compte")
     */
    public function resetting_compte($token, Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
      $em = $this->getDoctrine()->getManager();
      $user = $em->getRepository(User::class)->findUserByToken($token);
      dump($user);

      if ($user === null) {
          $this->addFlash('danger', 'Voulez-vous réinitialiser votre mot de passe ?.');
          return $this->redirectToRoute('resetting');
      }

      $form = $this->createFormBuilder()
        ->add('pwd', PasswordType::class, ['required' => true, 'label' => 'Nouveau mot de passe'])
        ->add('pwd1', PasswordType::class, ['required' => true, 'label' => 'Confirmation nouveau mot de passe'])
        ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
          $reset_token = $request->get('_csrf_token');
          if($this->isCsrfTokenValid('check_password', $reset_token))
          {
            $data = $form->getData();
            if($data['pwd'] != $data['pwd1'])
            {
              $this->addFlash('danger', 'La confirmation du nouveau mot de passe a échoué. Les mots de passe saisis ne sont pas identiques.');
              return $this->redirectToRoute('resetting_compte', ['token' => $user->getToken()]);
            }
            $password = $passwordEncoder->encodePassword($user, $data['pwd']);
            $user->setPassword($password);
            $user->setToken(NULL);
            $user->setDisabled(false);
            $user->setUpdatedAt(new \DateTime());
            $user->setPwdChangedAt(new \DateTime());
            $em->flush();
            $this->addFlash('success', 'Mot de passe réinitialisé avec succès. Veuillez vous reconnecter.');
            return $this->redirectToRoute('login');
          }
        }

      return $this->render(
          'Security/reinitialisation-password.html.twig',[
            'user' => $user,
            'form' => $form->createView()
          ]
      );
    }
}

?>
