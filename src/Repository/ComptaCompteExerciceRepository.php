<?php

namespace App\Repository;

use App\Entity\ComptaCompteExercice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ComptaCompteExercice|null find($id, $lockMode = null, $lockVersion = null)
 * @method ComptaCompteExercice|null findOneBy(array $criteria, array $orderBy = null)
 * @method ComptaCompteExercice[]    findAll()
 * @method ComptaCompteExercice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ComptaCompteExerciceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComptaCompteExercice::class);
    }

    public function comptesDuBilanOuDuResultat(string $categorie, string $type = null, int $id)
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
            ->join('c.exercice', 'e')
            ->join('c.compte', 'cpt')
            ->join('cpt.classe', 'cl')
            ->join('cl.type', 't')
            ->andWhere('t.id IN (:ids) AND e.id = :id')
            ->setParameter('id', $id)
            ->setParameter('ids', $ids)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findCompte(int $compteId, int $exerciceId)
    {
        return $this->createQueryBuilder('c')
            ->join('c.exercice', 'e')
            ->join('c.compte', 'cpt')
            ->join('cpt.classe', 'cl')
            ->join('cl.type', 't')
            ->andWhere('cpt.id = :compteId AND e.id = :exerciceId')
            ->setParameter('compteId', $compteId)
            ->setParameter('exerciceId', $exerciceId)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    // /**
    //  * @return ComptaCompteExercice[] Returns an array of ComptaCompteExercice objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ComptaCompteExercice
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
