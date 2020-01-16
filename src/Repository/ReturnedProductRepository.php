<?php

namespace App\Repository;

use App\Entity\ReturnedProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ReturnedProduct|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReturnedProduct|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReturnedProduct[]    findAll()
 * @method ReturnedProduct[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReturnedProductRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ReturnedProduct::class);
    }

    // /**
    //  * @return ReturnedProduct[] Returns an array of ReturnedProduct objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ReturnedProduct
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
