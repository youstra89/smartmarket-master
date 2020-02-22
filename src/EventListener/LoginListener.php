<?php

namespace App\EventListener;

use App\Entity\Connexion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LoginListener extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        // Get the User entity.
        $user = $event->getAuthenticationToken()->getUser();
        
        if($user->getIsDeleted() == true)
        {
            $container = $this->container;
            // dd($container);
            // "security.authorization_checker", "security.csrf.token_manager", "security.token_storage"
            $this->addFlash('danger', 'Ce compte d\'utilisateur a été supprimé.');
            $container->get('security.token_storage')->setToken(null);
            $container->get('session')->invalidate();
            return $this->redirectToRoute('logout');      
        }
        
        // Update your field here.
        $user->setLastLogin(new \DateTime());
        
        // Persist the data to database.
        $this->em->persist($user);
        
        // On enregistre la connexion
        $os = null;
        $ip = $_SERVER['REMOTE_ADDR'];
        $browser = $_SERVER['HTTP_USER_AGENT'];
        $connexion = new Connexion();
        $connexion->setUser($user);
        $connexion->setConnectedAt(new \DateTime());
        $connexion->setOperatingSystem($os);
        $connexion->setBrowser($browser);
        $connexion->setIpAddress($ip);
        $this->em->persist($connexion);

        $this->em->flush();
    }
}