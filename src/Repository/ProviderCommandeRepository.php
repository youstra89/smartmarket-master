<?php

namespace App\Repository;

use App\Entity\ProviderCommande;
use App\Entity\ProviderCommandeSearch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProviderCommande|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProviderCommande|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProviderCommande[]    findAll()
 * @method ProviderCommande[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProviderCommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProviderCommande::class);
    }

    public function commandesFournisseurs(ProviderCommandeSearch $search)
    {
      $query = $this->createQueryBuilder('p')
                    ->orderBy('p.date', 'DESC')
                    ->andWhere('p.is_deleted = :status')
                    ->setParameter('status', false)
                    ;

      if($search->getProvider()){
        $query = $query
          ->andWhere('p.provider = :provider')
          ->andWhere('p.is_deleted = :status')
          ->setParameter('status', false)
          ->setParameter('provider', $search->getProvider()->getId());
      }

      if($search->getProducts()->count() > 0){
        foreach($search->getProducts() as $k => $option){
          $query = $query
          ->andWhere(":product$k MEMBER OF p.product")
          ->setParameter("product$k", $option);
        }
      }

      return $query->getQuery();
    }

    public function lesCreanciers()
    {
        return $this->createQueryBuilder('c')
            ->where('c.ended = false')
            ->andWhere('c.is_deleted = :status')
            ->orderBy('c.date', 'DESC')  
            ->setParameter('status', false)  
            ->getQuery()
            ->getResult()
        ;
    }


    public function nombreCommandesDesCreanciers()
    {
        return $this->createQueryBuilder('c')
            ->select('p.id, COUNT(p.id) AS nbrCommandes')
            ->join('c.provider', 'p')
            ->where('c.ended = false')
            ->andWhere('c.is_deleted = :status')
            ->andWhere('p.is_deleted = :status')
            ->groupBy('p.id')  
            ->setParameter('status', false)  
            ->getQuery()
            ->getResult()
        ;
    }

    public function montant_net_a_payer_de_toutes_les_achats_de_la_date($dateActuelle)
    {
        return $this->createQueryBuilder('c')
            ->select('SUM(c.net_a_payer), c.date')
            ->where('c.date LIKE :dateActuelle')
            ->andWhere('c.is_deleted = :status')
            ->groupBy('c.date')
            ->setParameter('status', false)
            ->setParameter('dateActuelle', $dateActuelle.'%')
            ->getQuery()
            ->getResult()
        ;
    }
    
    
    public function tous_les_achats_du($dateActuelle)
    {
        return $this->createQueryBuilder('c')
            ->where('c.date LIKE :dateActuelle')
            ->andWhere('c.is_deleted = :status')
            ->setParameter('status', false)
            ->setParameter('dateActuelle', $dateActuelle.'%')
            ->getQuery()
            ->getResult()
        ;
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
