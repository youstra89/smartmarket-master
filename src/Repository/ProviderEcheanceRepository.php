<?php

namespace App\Repository;

use App\Entity\ProviderEcheance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method ProviderEcheance|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProviderEcheance|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProviderEcheance[]    findAll()
 * @method ProviderEcheance[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProviderEcheanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProviderEcheance::class);
    }

    // /**
    //  * @return ProviderEcheance[] Returns an array of ProviderEcheance objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ProviderEcheance
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
