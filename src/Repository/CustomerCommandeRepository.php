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
