<?php

namespace App\Repository;

use App\Entity\ComptaExercice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ComptaExercice|null find($id, $lockMode = null, $lockVersion = null)
 * @method ComptaExercice|null findOneBy(array $criteria, array $orderBy = null)
 * @method ComptaExercice[]    findAll()
 * @method ComptaExercice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ComptaExerciceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComptaExercice::class);
    }

    public function dernierExerciceEnCours()
    {
        $qb = $this->createQueryBuilder('c');

        $offset = 0;
        $limit = 1;
        $qb->select('c')
            ->where('c.acheve = false')
            ->orderBy('c.id', 'DESC')
            ->setFirstResult( $offset )
            ->setMaxResults( $limit )
        ;
        return $qb
            ->getQuery()
            ->getOneOrNullResult();
    }


    public function trouverExerciceDUneDate($date)
    {
        return $this->createQueryBuilder('e')
            ->where(':date BETWEEN e.date_debut AND e.date_fin')
            ->andWhere('e.is_deleted = :status')
            ->setParameter('status', false)
            ->setParameter('date', $date.'%')
            ->getQuery()
            ->getResult()
        ;
    }
    // /**
    //  * @return ComptaExercice[] Returns an array of ComptaExercice objects
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
    public function findOneBySomeField($value): ?ComptaExercice
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
