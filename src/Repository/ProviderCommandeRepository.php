<?php

namespace App\Repository;

use App\Entity\ProviderCommande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ProviderCommande|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProviderCommande|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProviderCommande[]    findAll()
 * @method ProviderCommande[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProviderCommandeRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ProviderCommande::class);
    }

    // /**
    //  * @return ProviderCommande[] Returns an array of ProviderCommande objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ProviderCommande
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
