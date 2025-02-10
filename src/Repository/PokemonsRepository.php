<?php

namespace App\Repository;

use App\Entity\Pokemons;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;

/**
 * @extends ServiceEntityRepository<Pokemons>
 */
class PokemonsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pokemons::class);
    }

    //    /**
    //     * @return Pokemons[] Returns an array of Pokemons objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Pokemons
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    // la funcion capture que recibe un pokemon y que devolviendo un valor entre 1 y 100 y si es entre 1 y 60 se le modificara el campo user_id por el id del usuario actual

    public function capture($pokemon)
    {
        $chance = rand(1, 100);
        if ($chance <= 60) {
            $pokemon->setUser_id($this->getEntityManager()->getRepository(User::class)->find($this->getUser()->getId()));
            $pokemon->setCaptured(true);
            $this->_em->persist($pokemon);
            $this->_em->flush();
            return $pokemon;
        } else if ($chance > 60 && $chance <= 100) {
            return null;
    }
}
}