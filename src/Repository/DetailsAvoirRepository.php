<?php

namespace App\Repository;

use App\Entity\DetailsAvoir;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DetailsAvoir|null find($id, $lockMode = null, $lockVersion = null)
 * @method DetailsAvoir|null findOneBy(array $criteria, array $orderBy = null)
 * @method DetailsAvoir[]    findAll()
 * @method DetailsAvoir[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DetailsAvoirRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DetailsAvoir::class);
    }

    // /**
    //  * @return DetailsAvoir[] Returns an array of DetailsAvoir objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DetailsAvoir
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
