<?php

namespace App\Repository;

use App\Entity\ProviderSettlement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Settlement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Settlement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Settlement[]    findAll()
 * @method Settlement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProviderSettlementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProviderSettlement::class);
    }

    public function reglementsIncomplets()
    {
        return $this->createQueryBuilder('p')
            ->select('c.id, SUM(p.amount) AS montant')
            ->join('p.commande', 'c')
            ->where('c.ended = FALSE')
            ->andWhere('p.is_deleted = :status')
            ->andWhere('c.is_deleted = :status')
            ->setParameter('status', false)
            ->groupBy('c.id')
            ->getQuery()
            ->getResult()
        ;
    }


    public function restesAPayer()
    {
        return $this->createQueryBuilder('s')
            ->select('p.id, (SUM(c.total_amount) - SUM(s.amount)) AS reste')
            ->join('s.commande', 'c')
            ->join('c.provider', 'p')
            ->andWhere('c.ended = FALSE')
            ->andWhere('s.is_deleted = :status')
            ->andWhere('c.is_deleted = :status')
            ->andWhere('p.is_deleted = :status')
            ->setParameter('status', false)
            ->groupBy('p.id')
            ->getQuery()
            ->getResult()
        ;
    }


    public function versementsAnterieurs(int $commandeId, object $settlement)
    {
        $settlementId = $settlement->getId();
        $settlementNumber = $settlement->getNumber();
        return $this->createQueryBuilder('p')
            ->join('p.commande', 'c')
            ->andWhere('p.is_deleted = :status')
            ->andWhere('p.number <= :settlementNumber')
            ->andWhere('c.id = :commandeId')
            ->setParameter('status', false)
            ->setParameter('settlementNumber', $settlementNumber)
            // ->setParameter('settlementId', $settlementId)
            ->setParameter('commandeId', $commandeId)
            ->getQuery()
            ->getResult()
        ;
    }


    public function versementsAnterieurs1(int $commandeId, int $settlementId, $date)
    {
        $manager = $this->getEntityManager()->getConnection();
        $requete_eentrees = 'SELECT p.id, s.amount, p.date, p.created_at, p.created_by_id, p.reference, u.username FROM provider_settlement p INNER JOIN provider_commande c ON c.id = p.commande_id JOIN user u ON u.id = p.created_by_id WHERE c.ended = :status AND p.is_deleted = 0 AND DATEDIFF(p.created_at, :date) >= 0 AND c.id = :commandeId;';
        $statement = $manager->prepare($requete_eentrees);
        $statement->bindValue('date', $date.'%');
        $statement->bindValue('status', false);
        $statement->bindValue('commandeId', $commandeId);
        $statement->execute();
        return $statement->fetchAll();
    }


    public function lastSettlement(int $commandeId)
    {
        $result = $this->createQueryBuilder('p')
            ->join('p.commande', 'c')
            ->andWhere('p.is_deleted = :status')
            ->andWhere('c.id = :commandeId')
            ->orderBy('p.date', 'DESC')
            ->setFirstResult(0)
            ->setMaxResults(1)
            ->setParameter('status', false)
            ->setParameter('commandeId', $commandeId)
            ->getQuery()
            ->getResult()
        ;
        
        return isset($result[0]) ? $result[0] : $result;
    }


    public function reglementMemeDate(int $commandeId, $date)
    {
        return $this->createQueryBuilder('p')
            ->join('p.commande', 'c')
            ->where('p.is_deleted = :status')
            ->andWhere('c.id = :commandeId')
            ->andWhere('p.created_at LIKE :date')
            ->setParameter('status', false)
            ->setParameter('date', $date.'%')
            ->setParameter('commandeId', $commandeId)
            ->getQuery()
            ->getResult()
        ;
    }

    
    public function lastNumber()
    {
        $today = (new \DateTime())->format('Y-m-d');
        $result = $this->createQueryBuilder('p')
            ->select('SUBSTRING(p.reference, 1, LENGTH(p.reference) - 16) AS reference')
            ->where('p.created_at LIKE :today')
            ->orderBy('p.id', 'DESC')
            ->setFirstResult(0)
            ->setMaxResults(1)
            ->setParameter('today', $today.'%')
            ->getQuery()
            ->getResult()
        ;
        $reference = (empty($result)) ? 0 : $result[0]["reference"];
        $result = ($reference === 0) ? 1 : $reference + 1;
        return substr_replace("0000",$result, -strlen($result));
    }

    // /**
    //  * @return Settlement[] Returns an array of Settlement objects
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
    public function findOneBySomeField($value): ?Settlement
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
