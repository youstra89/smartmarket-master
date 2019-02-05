<?php

namespace App\Repository;

use App\Entity\CustomerCommandeDetails;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method CustomerCommandeDetails|null find($id, $lockMode = null, $lockVersion = null)
 * @method CustomerCommandeDetails|null findOneBy(array $criteria, array $orderBy = null)
 * @method CustomerCommandeDetails[]    findAll()
 * @method CustomerCommandeDetails[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CustomerCommandeDetailsRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CustomerCommandeDetails::class);
    }

    // /**
    //  * @return CustomerCommandeDetails[] Returns an array of CustomerCommandeDetails objects
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
    public function findOneBySomeField($value): ?CustomerCommandeDetails
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
