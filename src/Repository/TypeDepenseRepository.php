<?php

namespace App\Repository;

use App\Entity\TypeDepense;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method TypeDepense|null find($id, $lockMode = null, $lockVersion = null)
 * @method TypeDepense|null findOneBy(array $criteria, array $orderBy = null)
 * @method TypeDepense[]    findAll()
 * @method TypeDepense[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypeDepenseRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, TypeDepense::class);
    }

    // /**
    //  * @return TypeDepense[] Returns an array of TypeDepense objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TypeDepense
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
