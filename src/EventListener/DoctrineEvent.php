<?php

namespace App\EventListener;

use App\Entity\Product;
use App\Entity\CustomerCommande;
use App\Entity\ProviderCommande;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use App\Controller\FonctionController;
use App\Entity\Settlement;

class DoctrineEvent implements EventSubscriber {
    private $fonction;

    public function __construct(FonctionController $fonction)
    {
        $this->fonction = $fonction;
    }

    public function getSubscribedEvents() {
        return array('prePersist', 'preUpdate');//les événements écoutés
    }

    public function prePersist(LifecycleEventArgs $args) {
        $entity = $args->getEntity();
        //Si c'est bien une entité Contact qui va être "persisté"
        $date = new \DateTime();
        $dateLimite = mktime(0, 0, 0, 05, 30, 2040);
        $dateActuelle = mktime(0, 0, 0, $date->format("m"), $date->format("d"), $date->format("Y"));
        if (($entity instanceof Product or $entity instanceof CustomerCommande or $entity instanceof ProviderCommande or $entity instanceof Settlement) and $dateActuelle > $dateLimite) {
            $this->fonction->flash();
            $entity->setCreatedAt(NULL);
            $entity->setCreatedBy(NULL);
            return $this->fonction->redirection();
            // return;
            // $entity->updateGmapData();//on met à jour les coordonnées via l'appel à google map
        }
    }

    public function preUpdate(LifecycleEventArgs $args) {
        $entity = $args->getEntity();
        $changeset = $args->getEntityManager()->getUnitOfWork()->getEntityChangeSet($entity);
        //Si c'est bien une entité Contact qui va être modifié
        $date = new \DateTime();
        $dateLimite = mktime(0, 0, 0, 05, 30, 2040);
        $dateActuelle = mktime(0, 0, 0, $date->format("m"), $date->format("d"), $date->format("Y"));
        if (($entity instanceof Product or $entity instanceof CustomerCommande or $entity instanceof ProviderCommande or $entity instanceof Settlement) and $dateActuelle > $dateLimite) {
            $this->fonction->flash();
            $entity->setCreatedAt(NULL);
            $entity->setCreatedBy(NULL);
            return $this->fonction->redirection();
            // return;
            //Si il y'a eu une mise a jour sur les propriétés en relation avec l'adresse (ici "address", "city" et "postalCode")
            if (array_key_exists("address", $changeset) || array_key_exists("city", $changeset) || array_key_exists("postalCode", $changeset)) {
                // $entity->updateGmapData();//on met à jour les coordonnées via l'appel à google map
            }
        }
    }

}