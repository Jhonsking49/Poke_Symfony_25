<?php

namespace App\Controller;

use App\Entity\Pokemons;
use App\Entity\Pokeplantilla;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
final class ApiController extends AbstractController
{

    //Obtener todos los pokemons
    #[Route('/pokemon', name: 'pokemon_list', methods: ['GET'])]
    public function getPokemons(EntityManagerInterface $entityManager): JsonResponse
    {
        $pokemons = $entityManager->getRepository(Pokemons::class)->findAll();

        $pokemonData = [];
        foreach ($pokemons as $pokemon) {
            $pokemonData[] = [
                'id' => $pokemon->getId(),
                'name' => $pokemon->getPokeplantilla()->getName(),
                'level' => $pokemon->getLevel(),
                'strength' => $pokemon->getStrength(),
                'state' => $pokemon->getState(),
                'trainer' => $pokemon->getUser() ? $pokemon->getUser()->getUsername() : null
            ];
        }
        
        return $this->json($pokemonData);
    }
     


     //Crear un pokemon
    #[Route('/pokemon', name: 'pokemon_create', methods: ['POST'])]
    public function createPokemon(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $pokemon = new Pokemons();
        $pokemon->setPokeplantilla($entityManager->getRepository(Pokeplantilla::class)->find($data['pokeplantilla_id']));
        $pokemon->setLevel($data['level']);
        $pokemon->setStrength($data['strength']);
        $pokemon->setState($data['state']);
        $pokemon->setUser($this->getUser());

        $entityManager->persist($pokemon);
        $entityManager->flush();

        return $this->json($pokemon, 201);
    }
    //Pokemons de un usuario
    #[Route('/pokemon/user/{id}', name: 'pokemon_user', methods: ['GET'])]
    public function getUserPokemons(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($id);
        
        if (!$user) {
            return $this->json(['message' => 'Usuario no encontrado'], 404);
        }
        
        $pokemons = $entityManager->getRepository(Pokemons::class)->findBy(['user' => $user]);
        
        $pokemonData = [];
        foreach ($pokemons as $pokemon) {
            $pokemonData[] = [
                'id' => $pokemon->getId(),
                'name' => $pokemon->getPokeplantilla()->getName(),
                'level' => $pokemon->getLevel(),
                'strength' => $pokemon->getStrength(),
                'state' => $pokemon->getState(),
                'trainer' => $pokemon->getUser()->getUsername()
            ];
        }

        return $this->json($pokemonData);   
    }   
    

    //Obtener un pokemon por su id
    #[Route('/pokemon/{id}', name: 'pokemon_show', methods: ['GET'])]
    public function getPokemon(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $pokemon = $entityManager->getRepository(Pokemons::class)->find($id);

        if (!$pokemon) {
            return $this->json(['message' => 'Pokemon not found'], 404);
        }

        $pokemonData = [
            'id' => $pokemon->getId(),
            'name' => $pokemon->getPokeplantilla()->getName(),
            'level' => $pokemon->getLevel(),
            'strength' => $pokemon->getStrength(),
            'state' => $pokemon->getState(),
            'trainer' => $pokemon->getUser() ? $pokemon->getUser()->getUsername() : null
        ];

        return $this->json($pokemonData);
    }

    
   
}

