<?php

namespace App\Repository;

use App\Entity\ComptaClasse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ComptaClasse|null find($id, $lockMode = null, $lockVersion = null)
 * @method ComptaClasse|null findOneBy(array $criteria, array $orderBy = null)
 * @method ComptaClasse[]    findAll()
 * @method ComptaClasse[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ComptaClasseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComptaClasse::class);
    }


    public function classesDuBilanOuDuResultat(string $categorie, string $type = null)
    {
        if($categorie == 'bilan'){
            if($type == 'actif')
                $ids = [1];
            elseif ($type == 'passif')
                $ids = [2];
            else
                $ids = [1, 2];
        }
        elseif ($categorie == 'resultat') {
            if($type == 'charges')
                $ids = [3];
            elseif ($type == 'produits')
                $ids = [4];
            else
                $ids = [3, 4];
        }
        elseif ($categorie == 'tous') {
            $ids = [1, 2, 3, 4];
        }
        return $this->createQueryBuilder('c')
            ->join('c.type', 't')
            ->andWhere('t.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /*
    public function findOneBySomeField($value): ?ComptaClasse
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
