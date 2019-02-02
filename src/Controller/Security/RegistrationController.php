<?php

namespace App\Controller\Security;

use App\Entity\User;
use App\Form\UserType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RegistrationController extends AbstractController
{
    /**
     * @Route("/register", name="registration")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder, \Swift_Mailer $mailer)
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

          $token = $request->get('_csrf_token');
          if($this->isCsrfTokenValid('registration', $token))
          {
            $password = $passwordEncoder->encodePassword($user, $user->getPassword());
            $user->setPassword($password);

            // Par defaut l'utilisateur aura toujours le rôle ROLE_USER
            $user->setRoles(['ROLE_USER']);
            $user->setToken($token);

            // On enregistre l'utilisateur dans la base
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $url = $this->generateUrl('activation_compte', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

            $message = (new \Swift_Message('Activation de compte Orientation Authentique'))
                ->setFrom('orientationauthentique@gmail.com')
                ->setTo($user->getEmail())
                ->setBody(
                    $this->renderView(
                        // templates/emails/registration.html.twig
                        'Emails/registration.html.twig', [
                        'url'  => $url,
                        'user' => $user
                      ]
                    ),
                    'text/html'
                );
            $mailer->send($message);

            $this->addFlash('success', 'Votre compte a été créé avec succès. Mais vous devez l\'activer par mail. Regardez dans votre email.');
            return $this->redirectToRoute('homepage');
          }
        }

        return $this->render(
            'Security/register.html.twig',
            array('form' => $form->createView())
        );
    }


    /**
     * @Route("activation-de-compte-orientation-authetique/{token}", name="activation_compte")
     */
    public function activation_compte($token)
    {
      $em = $this->getDoctrine()->getManager();
      $user = $em->getRepository(User::class)->findUserByToken($token);

      if ($user === null) {
          $this->addFlash('danger', 'Vous n\'avez pas de compte en attente d\'activation.');
          return $this->redirectToRoute('homepage');
      }
      $user->setToken(NULL);
      $user->setDisabled(false);
      $user->setUpdatedAt(new \DateTime());
      $em->flush();
      return $this->render(
          'Security/activation.html.twig',[
            'user' => $user
          ]
      );
    }
}

?>
