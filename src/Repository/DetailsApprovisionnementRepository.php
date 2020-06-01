<?php

namespace App\Repository;

use App\Entity\DetailsApprovisionnement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DetailsApprovisionnement|null find($id, $lockMode = null, $lockVersion = null)
 * @method DetailsApprovisionnement|null findOneBy(array $criteria, array $orderBy = null)
 * @method DetailsApprovisionnement[]    findAll()
 * @method DetailsApprovisionnement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DetailsApprovisionnementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DetailsApprovisionnement::class);
    }

    // /**
    //  * @return DetailsApprovisionnement[] Returns an array of DetailsApprovisionnement objects
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
    public function findOneBySomeField($value): ?DetailsApprovisionnement
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
