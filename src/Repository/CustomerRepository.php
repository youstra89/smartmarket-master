<?php

namespace App\Repository;

use App\Entity\Customer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Customer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Customer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Customer[]    findAll()
 * @method Customer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CustomerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    public function last_saved_customer()
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


    public function acomptes_clients()
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.acompte > :value')
            ->setParameter('value', 0)
            ->getQuery()
            ->getResult()
        ;
    }

    /*
    public function findOneBySomeField($value): ?Customer
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
