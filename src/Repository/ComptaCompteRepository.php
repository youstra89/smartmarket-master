<?php

namespace App\Repository;

use App\Entity\ComptaCompte;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ComptaCompte|null find($id, $lockMode = null, $lockVersion = null)
 * @method ComptaCompte|null findOneBy(array $criteria, array $orderBy = null)
 * @method ComptaCompte[]    findAll()
 * @method ComptaCompte[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ComptaCompteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComptaCompte::class);
    }

    // /**
    //  * @return ComptaCompte[] Returns an array of ComptaCompte objects
    //  */
    public function comptesDuBilanOuDuResultat(string $categorie, string $type = null)
    {
        /**
         * Cette méthode permet de sélectionner les comptes selon plusieurs critères:
         *      - catégorie qui peut être soit "bilan" soit "resultats"
         *      - type qui peut avoir comme valeur "actif", "passif", "charges" ou "produits"
         */
        $ids = $type == 1 ? [1, 2] : [3, 4];
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
            ->join('c.classe', 'cl')
            ->join('cl.type', 't')
            ->andWhere('t.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('c.numero', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /*
    public function findOneBySomeField($value): ?ComptaCompte
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
