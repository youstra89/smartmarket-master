<?php

namespace App\Repository;

use App\Entity\Depense;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Depense|null find($id, $lockMode = null, $lockVersion = null)
 * @method Depense|null findOneBy(array $criteria, array $orderBy = null)
 * @method Depense[]    findAll()
 * @method Depense[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DepenseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Depense::class);
    }

    public function differentDates()
    {
        $manager = $this->getEntityManager()->getConnection();
        $query = 'SELECT DISTINCT(SUBSTRING(d.date_depense, 1, 7)) AS `date` FROM depense d WHERE d.is_deleted = :status ORDER BY `date` ASC;';
        $statement = $manager->prepare($query);
        $statement->bindValue('status', false);
        $statement->execute();
        return $statement->fetchAll();
        ;
    }

    public function depense_mensuelle($date)
    {
        $manager = $this->getEntityManager()->getConnection();
        $requete_eentrees = 'SELECT SUM(d.amount) AS somme FROM depense d WHERE d.date_depense LIKE :date AND d.is_deleted = :status ORDER BY d.date_depense ASC;';
        $statement = $manager->prepare($requete_eentrees);
        $statement->bindValue('date', $date.'%');
        $statement->bindValue('status', false);
        $statement->execute();
        return $statement->fetchAll();
    }


    public function depensesDuMois($dateActuelle)
    {
        return $this->createQueryBuilder('d')
            ->where('d.date_depense LIKE :dateActuelle')
            ->andWhere('d.is_deleted = :status')
            ->orderBy('d.date_depense', 'DESC')
            ->setParameter('status', false)
            ->setParameter('dateActuelle', $dateActuelle.'%')
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return Depense[] Returns an array of Depense objects
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
    public function findOneBySomeField($value): ?Depense
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
