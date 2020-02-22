<?php

namespace App\Repository;

use App\Entity\Connexion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Connexion|null find($id, $lockMode = null, $lockVersion = null)
 * @method Connexion|null findOneBy(array $criteria, array $orderBy = null)
 * @method Connexion[]    findAll()
 * @method Connexion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConnexionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Connexion::class);
    }

    // /**
    //  * @return Connexion[] Returns an array of Connexion objects
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
    public function findOneBySomeField($value): ?Connexion
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
