<?php

namespace App\Repository;

use App\Entity\CustomerCommande;
use App\Entity\CustomerCommandeSearch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CustomerCommande|null find($id, $lockMode = null, $lockVersion = null)
 * @method CustomerCommande|null findOneBy(array $criteria, array $orderBy = null)
 * @method CustomerCommande[]    findAll()
 * @method CustomerCommande[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CustomerCommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerCommande::class);
    }

    // public function commandesClients(CustomerCommandeSearch $search)
    public function commandesClients(int $exerciceId)
    {
      $query = $this->createQueryBuilder('c')
                    ->join('c.exercice', 'e')
                    ->andWhere('e.id = :exerciceId')
                    ->andWhere('c.status = :status')
                    ->andWhere('c.is_deleted = false')
                    ->setParameter('exerciceId', $exerciceId)
                    ->setParameter('status', "LIVREE")
                    ->orderBy('c.date', 'DESC')
                    ;


      return $query->getQuery()->getResult();
    }

    public function commandesClientsAPreparer()
    {
      return $this->createQueryBuilder('c')
                    ->andWhere('c.status = :status')
                    ->andWhere('c.is_deleted = false')
                    ->setParameter('status', "ENREGISTREE")
                    ->orderBy('c.date', 'DESC')
                    ->getQuery();
    }

    public function venteDuJour($date)
    {
      return $this->createQueryBuilder('c')
          ->andWhere('c.date LIKE :date')
          ->andWhere('c.status = :commandeStatus')
          ->andWhere('c.is_deleted = :status')
          ->setParameter('status', false)
          ->setParameter('commandeStatus', "LIVREE")
          ->setParameter('date', '%'.$date.'%')
          ->orderBy('c.id', 'DESC')
          ->getQuery()
          ->getResult()
      ;
    }

    public function differentDates()
    {
        $manager = $this->getEntityManager()->getConnection();
        $query = 'SELECT DISTINCT(SUBSTRING(cc.created_at, 1, 7)) AS `date` FROM customer_commande cc WHERE cc.is_deleted = :status AND cc.status = :commandeStatus ORDER BY `date` ASC;';
        $statement = $manager->prepare($query);
        $statement->bindValue('status', false);
        $statement->bindValue('commandeStatus', "LIVREE");
        $statement->execute();
        return $statement->fetchAll();
        ;
    }

    public function montantTotalHorsTaxeDeToutesLesVenteDUnMois($dateActuelle)
    {
        return $this->createQueryBuilder('c')
            ->select('SUM(c.total_amount), c.date')
            ->where('c.date LIKE :dateActuelle')
            ->andWhere('c.is_deleted = :status')
            ->andWhere('c.status = :commandeStatus')
            ->groupBy('c.date')
            ->setParameter('status', false)  
            ->setParameter('commandeStatus', "LIVREE")  
            ->setParameter('dateActuelle', $dateActuelle.'%')
            ->getQuery()
            ->getResult()
        ;
    }

    public function montant_net_a_payer_de_toutes_les_ventes_de_la_date($dateActuelle)
    {
        return $this->createQueryBuilder('c')
            ->select('SUM(c.net_a_payer), c.date')
            ->where('c.date LIKE :dateActuelle')
            ->andWhere('c.is_deleted = :status')
            ->andWhere('c.status = :commandeStatus')
            ->groupBy('c.date')
            ->setParameter('status', false)  
            ->setParameter('commandeStatus', "LIVREE")  
            ->setParameter('dateActuelle', $dateActuelle.'%')
            ->getQuery()
            ->getResult()
        ;
    }

    public function toutes_les_ventes_du($dateActuelle)
    {
        return $this->createQueryBuilder('c')
            ->where('c.date LIKE :dateActuelle')
            ->andWhere('c.is_deleted = :status')
            ->andWhere('c.status = :commandeStatus')
            ->setParameter('status', false)  
            ->setParameter('commandeStatus', "LIVREE")  
            ->setParameter('dateActuelle', $dateActuelle.'%')
            ->getQuery()
            ->getResult()
        ;
    }

    public function totalDesRemiseDeToutesLesVenteDUnMois($dateActuelle)
    {
        return $this->createQueryBuilder('c')
            ->select('SUM(c.remise), c.date')
            ->where('c.date LIKE :dateActuelle')
            ->andWhere('c.is_deleted = :status')
            ->andWhere('c.status = :commandeStatus')
            ->groupBy('c.date')
            ->setParameter('status', false)  
            ->setParameter('commandeStatus', "LIVREE")  
            ->setParameter('dateActuelle', $dateActuelle.'%')
            ->getQuery()
            ->getResult()
        ;
    }

    public function monthSells($date)
    {
        $manager = $this->getEntityManager()->getConnection();
        $requete_eentrees = 'SELECT SUM(cc.total_amount) AS somme FROM customer_commande cc WHERE cc.date LIKE :date AND cc.is_deleted = :status AND cc.status = :commandeStatus ORDER BY cc.date ASC;';
        $statement = $manager->prepare($requete_eentrees);
        $statement->bindValue('date', $date.'%');
        $statement->bindValue('commandeStatus', "LIVREE");
        $statement->bindValue('status', false);
        $statement->execute();
        return $statement->fetchAll();
    }

    public function lesDebiteurs()
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.ended = false')
            ->andWhere('c.status = :commandeStatus')
            ->andWhere('c.is_deleted = :status')
            ->orderBy('c.date', 'DESC')  
            ->setParameter('status', false)  
            ->setParameter('commandeStatus', "LIVREE")  
            ->getQuery()
            ->getResult()
        ;
    }


    public function nombreCommandesDesDebiteurs()
    {
        return $this->createQueryBuilder('c')
            ->select('cus.id, COUNT(cus.id) AS nbrCommandes')
            ->join('c.customer', 'cus')
            ->where('c.ended = false')
            ->andWhere('c.is_deleted = :status')
            ->andWhere('cus.is_deleted = :status')
            ->andWhere('c.status = :commandeStatus')
            ->groupBy('cus.id')  
            ->setParameter('status', false)  
            ->setParameter('commandeStatus', "LIVREE")  
            ->getQuery()
            ->getResult()
        ;
    }
    // /**
    //  * @return CustomerCommande[] Returns an array of CustomerCommande objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CustomerCommande
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
