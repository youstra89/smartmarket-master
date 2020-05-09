<?php

namespace App\Repository;

use App\Entity\ComptaType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ComptaType|null find($id, $lockMode = null, $lockVersion = null)
 * @method ComptaType|null findOneBy(array $criteria, array $orderBy = null)
 * @method ComptaType[]    findAll()
 * @method ComptaType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ComptaTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComptaType::class);
    }

    // /**
    //  * @return ComptaType[] Returns an array of ComptaType objects
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
    public function findOneBySomeField($value): ?ComptaType
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
