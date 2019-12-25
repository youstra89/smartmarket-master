<?php


namespace App\Service;

use Symfony\Component\Security\Core\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CheckConnectedUser
{
    private $security;

    public function __construct(Security $security)
    {
        // Avoid calling getUser() in the constructor: auth may not
        // be complete yet. Instead, store the entire Security object.
        $this->security = $security;
        // $this->abs = $abs;
    }

    public function getAccess()
    {
      $user = $this->security->getUser();
      return empty($user);
      // if (empty($user)) {
      //   return $this->abs->redirectToRoute('login');
      // }
    }
}
