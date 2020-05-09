<?php

namespace App\Repository;

use App\Entity\ComptaEcriture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ComptaEcriture|null find($id, $lockMode = null, $lockVersion = null)
 * @method ComptaEcriture|null findOneBy(array $criteria, array $orderBy = null)
 * @method ComptaEcriture[]    findAll()
 * @method ComptaEcriture[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ComptaEcritureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComptaEcriture::class);
    }

    
    public function last_saved()
    {
        $qb = $this->createQueryBuilder('c');

        $offset = 0;
        $limit = 1;
        $qb->select('c')
            ->orderBy('c.id', 'DESC')
            ->setFirstResult( $offset )
            ->setMaxResults( $limit )
        ;
        return $qb
            ->getQuery()
            ->getOneOrNullResult();
    }
    

    public function ecrituresDeLExercice($id)
    {
        return $this->createQueryBuilder('c')
            // ->join('c.exercice', 'e')
            // ->andWhere('e.id = :id')
            ->andWhere('c.exercice = :id')
            ->setParameter('id', $id)
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /*
    public function findOneBySomeField($value): ?ComptaEcriture
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
