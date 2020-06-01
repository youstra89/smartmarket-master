<?php

namespace App\Repository;

use App\Entity\Acompte;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Acompte|null find($id, $lockMode = null, $lockVersion = null)
 * @method Acompte|null findOneBy(array $criteria, array $orderBy = null)
 * @method Acompte[]    findAll()
 * @method Acompte[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AcompteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Acompte::class);
    }


    public function acomptesDuJour($dateActuelle)
    {
        return $this->createQueryBuilder('a')
            ->where('a.created_at LIKE :dateActuelle')
            ->andWhere('a.is_deleted = :status')
            ->setParameter('status', false)
            ->setParameter('dateActuelle', $dateActuelle.'%')
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return Acompte[] Returns an array of Acompte objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Acompte
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
