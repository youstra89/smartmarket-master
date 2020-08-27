<?php

namespace App\Repository;

use App\Entity\RetraitAcompte;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RetraitAcompte|null find($id, $lockMode = null, $lockVersion = null)
 * @method RetraitAcompte|null findOneBy(array $criteria, array $orderBy = null)
 * @method RetraitAcompte[]    findAll()
 * @method RetraitAcompte[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RetraitAcompteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RetraitAcompte::class);
    }

    public function retraitsAcomptesDuJour($dateActuelle)
    {
        return $this->createQueryBuilder('r')
            ->where('r.created_at LIKE :dateActuelle')
            ->andWhere('r.is_deleted = :status')
            ->setParameter('status', false)
            ->setParameter('dateActuelle', $dateActuelle.'%')
            ->getQuery()
            ->getResult()
        ;
    }

    public function retrait_acompte_sur_periode(int $customerId, $debut, $fin)
    {
        return $this->createQueryBuilder('r')
            ->join('r.customer', 'c')
            ->andWhere('c.id = :customerId')
            ->andWhere('r.is_deleted = :status')
            ->andWhere('r.date >= :debut')
            ->andWhere('r.date <= :fin')
            ->setParameter('status', false)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->setParameter('customerId', $customerId)
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return RetraitAcompte[] Returns an array of RetraitAcompte objects
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
    public function findOneBySomeField($value): ?RetraitAcompte
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
