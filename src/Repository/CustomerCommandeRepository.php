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
      $query = $this->createQueryBuilder('c')
                    ->andWhere('c.status = :status')
                    ->andWhere('c.is_deleted = false')
                    ->setParameter('status', "LIVREE")
                    ->orderBy('c.date', 'DESC')
                    ;

      if($search->getCustomer()){
        $query = $query
          ->andWhere('clt.customer = :customer')
          ->andWhere('clt.is_deleted = :status')
          ->setParameter('status', false)
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

    public function commandesClientsAPreparer(CustomerCommandeSearch $search)
    {
      $query = $this->createQueryBuilder('c')
                    ->andWhere('c.status = :status')
                    ->andWhere('c.is_deleted = false')
                    ->setParameter('status', "ENREGISTREE")
                    ->orderBy('c.date', 'DESC')
                    ;

      if($search->getCustomer()){
        $query = $query
          ->andWhere('clt.customer = :customer')
          ->andWhere('clt.is_deleted = :status')
          ->setParameter('status', false)
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
          ->where('c.date LIKE :date')
          ->andWhere('c.is_deleted = :status')
          ->setParameter('status', false)
          ->setParameter('date', '%'.$date.'%')
          ->orderBy('c.id', 'DESC')
          ->getQuery()
          ->getResult()
      ;
    }

    public function differentDates()
    {
        $manager = $this->getEntityManager()->getConnection();
        $query = 'SELECT DISTINCT(SUBSTRING(cc.created_at, 1, 7)) AS `date` FROM customer_commande cc WHERE cc.is_deleted = :status ORDER BY `date` ASC;';
        $statement = $manager->prepare($query);
        $statement->bindValue('status', false);
        $statement->execute();
        return $statement->fetchAll();
        ;
    }

    public function monthlySelling($dateActuelle)
    {
        return $this->createQueryBuilder('c')
            ->select('SUM(c.total_amount), c.date')
            ->where('c.date LIKE :dateActuelle')
            ->andWhere('c.is_deleted = :status')
            ->groupBy('c.date')
            ->setParameter('status', false)  
            ->setParameter('dateActuelle', $dateActuelle.'%')
            ->getQuery()
            ->getResult()
        ;
    }

    public function monthSells($date)
    {
        $manager = $this->getEntityManager()->getConnection();
        $requete_eentrees = 'SELECT SUM(cc.total_amount) AS somme FROM customer_commande cc WHERE cc.date LIKE :date AND cc.is_deleted = :status ORDER BY cc.date ASC;';
        $statement = $manager->prepare($requete_eentrees);
        $statement->bindValue('date', $date.'%');
        $statement->bindValue('status', false);
        $statement->execute();
        return $statement->fetchAll();
    }

    public function lesDebiteurs()
    {
        return $this->createQueryBuilder('c')
            ->where('c.ended = false')
            ->andWhere('c.is_deleted = :status')
            ->orderBy('c.date', 'DESC')  
            ->setParameter('status', false)  
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
