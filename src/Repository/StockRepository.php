<?php

namespace App\Repository;

use App\Entity\Stock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Stock|null find($id, $lockMode = null, $lockVersion = null)
 * @method Stock|null findOneBy(array $criteria, array $orderBy = null)
 * @method Stock[]    findAll()
 * @method Stock[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stock::class);
    }

    public function storeProducts(int $storeId)
    {
        return $this->createQueryBuilder('s')
            ->join('s.product', 'p')
            ->join('s.store', 'st')
            ->andWhere('st.id = :storeId')
            ->setParameter('storeId', $storeId)
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getStockBystoreIdAndProductId(int $productId, int $storeId)
    {
        return $this->createQueryBuilder('s')
            ->join('s.product', 'p')
            ->join('s.store', 'st')
            ->andWhere('p.id = :productId')
            ->andWhere('st.id = :storeId')
            ->setParameter('productId', $productId)
            ->setParameter('storeId', $storeId)
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByReferenceAndStoreId(string $reference, int $storeId)
    {
        return $this->createQueryBuilder('s')
            ->join('s.product', 'p')
            ->join('s.store', 'st')
            ->andWhere('p.reference = :reference')
            ->andWhere('st.id = :storeId')
            ->setParameter('reference', $reference)
            ->setParameter('storeId', $storeId)
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult()[0]
        ;
    }

    // /**
    //  * @return Stock[] Returns an array of Stock objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Stock
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
