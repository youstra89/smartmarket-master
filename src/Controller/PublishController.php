<?php
// src/Controller/PublishController.php
namespace App\Controller;

use Symfony\Component\Mercure\Update;
use Symfony\Component\Mercure\Publisher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PublishController extends AbstractController
{
    public function __invoke(Publisher $publisher): Response
    {
        $update = new Update(
            'http://example.com/books/1',
            json_encode(['status' => 'OutOfStock'])
        );

        // The Publisher service is an invokable object
        $publisher($update);

        return new Response('published!');
    }


    /**
     * @Route("/homeping", name="homeping")
     */
    public function homePing()
    {
      return $this->render("ping.html.twig", ["current" => "ping"]);
    }

    // /**
    //  * @Route("/ping", name="ping", methods={"POST"})
    //  * @param Publisher $publisher
    //  */
    // public function ping(Publisher $publisher)
    // {
    //   $update = new Update("http://monsite.com/ping", "[]");
    //   $publisher($update);
    //   return $this->redirectToRoute('homeping', ["current" => "ping"]);
    //   return $this->render("ping.html.twig");
    // }
}


// // change these values accordingly to your hub installation
// define('HUB_URL', 'https://demo.mercure.rocks/.well-known/mercure');
// define('JWT', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtZXJjdXJlIjp7InN1YnNjcmliZSI6WyJmb28iLCJiYXIiXSwicHVibGlzaCI6WyJmb28iXX19.LRLvirgONK13JgacQ_VbcjySbVhkSmHy3IznH3tA9PM');

// use Symfony\Component\Mercure\Jwt\StaticJwtProvider;
// use Symfony\Component\Mercure\Publisher;
// use Symfony\Component\Mercure\Update;

// $publisher = new Publisher(HUB_URL, new StaticJwtProvider(JWT));
// // Serialize the update, and dispatch it to the hub, that will broadcast it to the clients
// $id = $publisher(new Update('https://example.com/books/1.jsonld', 'Hi from Symfony!', ['target1', 'target2']));
