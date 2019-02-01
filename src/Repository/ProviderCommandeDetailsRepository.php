<?php

namespace App\Repository;

use App\Entity\ProviderCommandeDetails;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ProviderCommandeDetails|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProviderCommandeDetails|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProviderCommandeDetails[]    findAll()
 * @method ProviderCommandeDetails[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProviderCommandeDetailsRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ProviderCommandeDetails::class);
    }

    // /**
    //  * @return ProviderCommandeDetails[] Returns an array of ProviderCommandeDetails objects
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
    public function findOneBySomeField($value): ?ProviderCommandeDetails
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
