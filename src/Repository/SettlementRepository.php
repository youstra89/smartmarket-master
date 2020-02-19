<?php

namespace App\Repository;

use App\Entity\Settlement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Settlement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Settlement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Settlement[]    findAll()
 * @method Settlement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SettlementRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Settlement::class);
    }

    public function reglementsIncomplets()
    {
        return $this->createQueryBuilder('s')
            ->select('c.id, SUM(s.amount) AS montant')
            ->join('s.commande', 'c')
            ->where('c.ended = FALSE')
            ->andWhere('s.is_deleted = :status')
            ->andWhere('c.is_deleted = :status')
            ->setParameter('status', false)
            ->groupBy('c.id')
            ->getQuery()
            ->getResult()
        ;
    }


    public function versementsAnterieurs(int $commandeId, object $settlement)
    {
        $settlementId = $settlement->getId();
        $settlementNumber = $settlement->getNumber();
        return $this->createQueryBuilder('s')
            ->join('s.commande', 'c')
            ->andWhere('s.is_deleted = :status')
            ->andWhere('s.number <= :settlementNumber')
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
        $requete_eentrees = 'SELECT s.id, s.amount, s.date, s.created_at, s.created_by_id, s.reference, u.username FROM settlement s INNER JOIN customer_commande c ON c.id = s.commande_id JOIN user u ON u.id = s.created_by_id WHERE c.ended = :status AND s.is_deleted = 0 AND DATEDIFF(s.created_at, :date) >= 0 AND c.id = :commandeId;';
        $statement = $manager->prepare($requete_eentrees);
        $statement->bindValue('date', $date.'%');
        $statement->bindValue('status', false);
        $statement->bindValue('commandeId', $commandeId);
        $statement->execute();
        return $statement->fetchAll();
    }


    public function lastSettlement(int $commandeId)
    {
        $result = $this->createQueryBuilder('s')
            ->join('s.commande', 'c')
            ->andWhere('s.is_deleted = :status')
            ->andWhere('c.id = :commandeId')
            ->orderBy('s.date', 'DESC')
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
        return $this->createQueryBuilder('s')
            ->join('s.commande', 'c')
            ->where('s.is_deleted = :status')
            ->andWhere('c.id = :commandeId')
            ->andWhere('s.created_at LIKE :date')
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
        $result = $this->createQueryBuilder('s')
            ->select('SUBSTRING(s.reference, 1, LENGTH(s.reference) - 16) AS reference')
            ->where('s.created_at LIKE :today')
            ->orderBy('s.id', 'DESC')
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
