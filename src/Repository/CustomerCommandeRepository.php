<?php

namespace App\Repository;

use App\Entity\CustomerCommande;
use App\Entity\CustomerCommandeSearch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method CustomerCommande|null find($id, $lockMode = null, $lockVersion = null)
 * @method CustomerCommande|null findOneBy(array $criteria, array $orderBy = null)
 * @method CustomerCommande[]    findAll()
 * @method CustomerCommande[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CustomerCommandeRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CustomerCommande::class);
    }

    public function commandesClients(CustomerCommandeSearch $search)
    {
      $query = $this->createQueryBuilder('clt')
                    ->join('clt.commande', 'c')
                    ->addSelect('c')
                    ->orderBy('c.date', 'DESC')
                    ;

      if($search->getCustomer()){
        $query = $query
          ->andWhere('clt.customer = :customer')
          ->setParameter('customer', $search->getCustomer()->getId());
      }

      if($search->getProducts()->count() > 0){
        foreach($search->getProducts() as $k => $option){
          $query = $query
          ->andWhere(":product$k MEMBER OF clt.customerCommandeDetails")
          ->setParameter("product$k", $option);
        }
      }

      return $query->getQuery();
    }

    public function dayCommande($date)
    {
      return $this->createQueryBuilder('c')
          ->join('c.commande', 'cmd')
          ->addSelect('cmd')
          ->where('cmd.date LIKE :date')
          ->setParameter('date', '%'.$date.'%')
          ->orderBy('c.id', 'DESC')
          ->getQuery()
          ->getResult()
      ;
    }

    public function differentDates()
    {
        $manager = $this->getEntityManager()->getConnection();
        $query = 'SELECT DISTINCT(SUBSTRING(c.created_at, 1, 7)) AS `date` FROM customer_commande cc JOIN commande c ON cc.commande_id = c.id ORDER BY `date` ASC;';
        $statement = $manager->prepare($query);
        $statement->execute();
        return $statement->fetchAll();
        ;
    }

    public function monthlySelling($dateActuelle)
    {
        return $this->createQueryBuilder('c')
            ->join('c.commande', 'cmd')
            ->select('SUM(cmd.total_amount), cmd.date')
            ->where('cmd.date LIKE :dateActuelle')
            ->groupBy('cmd.date')
            ->setParameter('dateActuelle', $dateActuelle.'%')
            ->getQuery()
            ->getResult()
        ;
    }

    public function monthSells($date)
    {
        $manager = $this->getEntityManager()->getConnection();
        $requete_eentrees = 'SELECT SUM(c.total_amount) AS somme FROM customer_commande cc JOIN commande c ON cc.commande_id = c.id WHERE c.date LIKE :date ORDER BY c.date ASC, c.id ASC;';
        $statement = $manager->prepare($requete_eentrees);
        $statement->bindValue('date', $date.'%');
        $statement->execute();
        return $statement->fetchAll();
        return $this->createQueryBuilder('c')
            ->join('c.commande', 'cmd')
            ->addSelect('cmd')
            ->select('SUM(cmd.total_amount), cmd.date')
            ->where('cmd.date LIKE :date')
            ->groupBy('cmd.date')
            ->setParameter('date', $date.'%')
            ->orderBy('cmd.date', 'ASC')
            ->orderBy('cmd.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function lesDebiteurs()
    {
        return $this->createQueryBuilder('c')
            ->join('c.commande', 'cmd')
            ->addSelect('cmd')
            ->where('cmd.ended = false')
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
