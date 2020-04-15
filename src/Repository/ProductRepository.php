<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\ProductSearch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findSelectedProducts($ids)
    {
        return $this->createQueryBuilder('p')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAllProductsQuery(ProductSearch $search)
    {
      $query = $this->createQueryBuilder('p');

      if($search->getCategory()){
        $query = $query
          ->join('p.category', 'c')
          ->andWhere('c.id = :categoryId')
          ->setParameter('categoryId', $search->getCategory()->getId());
      }

      if($search->getMark()){
        $query = $query
          ->join('p.mark', 'm')
          ->andWhere('m.id = :markId')
          ->setParameter('markId', $search->getMark()->getId());
      }

      if($search->getDescription()){
        $query = $query
          ->andWhere('p.description LIKE :description')
          ->setParameter('description', '%'.addcslashes($search->getDescription(), '%_').'%');
      }

      return $query->getQuery();
    }

    public function allProductsByCategory()
    {
        $query = $this->createQueryBuilder('p');
        $query = $query
            ->join('p.category', 'c')
            ->addSelect('c')
            ->orderBy('c.name')
            ->addOrderBy('c.id')
            ->where('p.is_deleted = :status')
            ->andWhere('c.is_deleted = :status')
            ->setParameter('status', false)

        ;

        return $query->getQuery()->getResult();
    }

    public function last_saved_product()
    {
        $qb = $this->createQueryBuilder('p');

        $offset = 0;
        $limit = 1;
        $qb->select('p')
            ->orderBy('p.id', 'DESC')
            ->setFirstResult( $offset )
            ->setMaxResults( $limit )
        ;
        return $qb
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findProductsByIds(array $idsProducts)
    {
        $query = $this->createQueryBuilder('p');
        $query = $query
            ->where('p.is_deleted = :status')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('status', false)
            ->setParameter('ids', $idsProducts)

        ;

        return $query->getQuery()->getResult();
    }

    // /**
    //  * @return Product[] Returns an array of Product objects
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
    public function findOneBySomeField($value): ?Product
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
