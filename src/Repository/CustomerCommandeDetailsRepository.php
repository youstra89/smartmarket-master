<?php

namespace App\Repository;

use App\Entity\CustomerCommandeDetails;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method CustomerCommandeDetails|null find($id, $lockMode = null, $lockVersion = null)
 * @method CustomerCommandeDetails|null findOneBy(array $criteria, array $orderBy = null)
 * @method CustomerCommandeDetails[]    findAll()
 * @method CustomerCommandeDetails[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CustomerCommandeDetailsRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CustomerCommandeDetails::class);
    }

    public function test($dateActuelle = null)
    {
        return $this->createQueryBuilder('c')
            ->select('SUM(p.unit_price - p.purchasing_price), com.date')
            ->join('c.commande', 'com')
            ->join('com.product', 'p')
            ->addSelect('p')
            ->where('c.is_deleted = :status')
            ->andWhere('p.is_deleted = :status')
            ->andWhere('com.is_deleted = :status')
            // ->where('c.date LIKE :dateActuelle')
            ->groupBy('com.date')
            ->setParameter('status', false)
            ->getQuery()
            ->getResult()
        ;
    }

    public function benefice_journalier($date)
    {
        $manager = $this->getEntityManager()->getConnection();
        $requete_eentrees = 'SELECT SUM((p.unit_price - p.purchasing_price) * ccd.quantity) AS benefice, cc.date FROM customer_commande_details ccd JOIN customer_commande cc ON ccd.commande_id = cc.id JOIN product p ON ccd.product_id = p.id WHERE cc.date LIKE :date AND p.is_deleted = :status AND cc.is_deleted = :status GROUP BY cc.date;';
        $statement = $manager->prepare($requete_eentrees);
        $statement->bindValue('date', $date.'%');
        $statement->bindValue('status', false);
        $statement->execute();
        return $statement->fetchAll();
    }


    public function benefice_mensuel($date)
    {
        $manager = $this->getEntityManager()->getConnection();
        $requete_eentrees = 'SELECT SUM((p.unit_price - p.purchasing_price) * ccd.quantity) AS benefice, CONCAT(YEAR(cc.date), "-", MONTH(cc.date)) AS dateCC FROM customer_commande_details ccd JOIN customer_commande cc ON ccd.commande_id = cc.id JOIN product p ON ccd.product_id = p.id WHERE cc.date LIKE :date AND p.is_deleted = :status AND cc.is_deleted = :status GROUP BY dateCC;';
        $statement = $manager->prepare($requete_eentrees);
        $statement->bindValue('date', $date.'%');
        $statement->bindValue('status', false);
        $statement->execute();
        return $statement->fetchAll()[0];
    }

    // /**
    //  * @return CustomerCommandeDetails[] Returns an array of CustomerCommandeDetails objects
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
    public function findOneBySomeField($value): ?CustomerCommandeDetails
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
