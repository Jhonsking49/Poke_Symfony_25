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

    //funcion que se trae todos los pokemons los cuales el user_id sea igual a null
    public function findAllByUserIdNull()
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->where('u.id IS NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * Intenta capturar un pokemon con 60% de probabilidad
     * @param Pokemons $pokemon El pokemon a capturar
     * @param int $userId El ID del usuario que intenta capturar
     * @return array Resultado de la captura con mensaje y éxito
     */
    public function intentarCapturarPokemon(Pokemons $pokemon, int $userId): array
    {
        $numeroAleatorio = rand(1, 100);
        
        if ($numeroAleatorio <= 60) {
            $user = $this->getEntityManager()
                ->getRepository('App\Entity\User')
                ->find($userId);
            
            if (!$user) {
                return [
                    'mensaje' => 'Usuario no encontrado',
                    'exito' => false
                ];
            }
            
            $pokemon->setUser($user);
            $this->getEntityManager()->persist($pokemon);
            $this->getEntityManager()->flush();
            
            return [
                'mensaje' => 'Pokemon capturado con éxito',
                'exito' => true
            ];
        }
        
        return [
            'mensaje' => 'El Pokemon no ha sido capturado',
            'exito' => false
        ];
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