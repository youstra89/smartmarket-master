<?php

namespace App\Repository;

use App\Entity\Echeance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Echeance|null find($id, $lockMode = null, $lockVersion = null)
 * @method Echeance|null findOneBy(array $criteria, array $orderBy = null)
 * @method Echeance[]    findAll()
 * @method Echeance[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EcheanceRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Echeance::class);
    }


    public function toutesLesEcheances()
    {
        return $this->createQueryBuilder('e')
            ->join('e.commande', 'c')
            ->andWhere('c.ended = :status')
            ->andWhere('c.is_deleted = :status')
            ->andWhere('e.is_deleted = :status')
            ->setParameter('status', false)
            ->getQuery()
            ->getResult()
        ;
    }


    public function differentesDatesEcheances()
    {
        $manager = $this->getEntityManager()->getConnection();
        $query = 'SELECT DISTINCT(SUBSTRING(e.created_at, 1, 7)) AS `date` FROM echeance e WHERE e.is_paid = :status ORDER BY `date` ASC;';
        $statement = $manager->prepare($query);
        $statement->bindValue('status', false);
        $statement->execute();
        return $statement->fetchAll();
        ;
    }

    // /**
    //  * @return Echeance[] Returns an array of Echeance objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Echeance
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
