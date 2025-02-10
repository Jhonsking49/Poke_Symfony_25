<?php

namespace App\Controller;

use App\Entity\Pokemons;
use App\Entity\Pokeplantilla;
use App\Form\PokemonsType;
use App\Repository\PokemonsRepository;
use App\Repository\PokeplantillaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pokemons')]
final class PokemonsController extends AbstractController
{
    #[Route(name: 'app_pokemons_index', methods: ['GET'])]
    public function index(PokemonsRepository $pokemonsRepository): Response
    {
        return $this->render('pokemons/index.html.twig', [
            'pokemons' => $pokemonsRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_pokemons_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $pokemon = new Pokemons();
        $form = $this->createForm(PokemonsType::class, $pokemon);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($pokemon);
            $entityManager->flush();

            return $this->redirectToRoute('app_pokemons_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pokemons/new.html.twig', [
            'pokemon' => $pokemon,
            'form' => $form,
        ]);
    }
    #[Route('/init', name: 'app_pokemons_init', methods: ['GET'])]
    public function init(PokemonsRepository $pokemonsRepository, EntityManagerInterface $entityManager, PokeplantillaRepository $pokeplantillaRepository): Response
    {
        for ($i = 1; $i < 50; $i++) { 
            // Fetch Pokemon data from PokeAPI
            $pokemonApiUrl = "https://pokeapi.co/api/v2/pokemon/{$i}";
            $pokemonData = json_decode(file_get_contents($pokemonApiUrl), true);
    
            $pokemon = new Pokemons();
            $pokePlantilla = new Pokeplantilla();
    
            // Set Pokemon name from API response
            $pokePlantilla->setName($pokemonData['name']);
    
            // Set type (first type)
            $pokePlantilla->setType($pokemonData['types'][0]['type']['name']);
    
            $pokemon->setLevel(1);
            $pokemon->setStrength(10);
            $pokemon->setImg("https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/dream-world/{$i}.svg");
            $pokemon->setUser(null);
            
            // Set the Pokeplantilla for this Pokemon
            $pokemon->setPokeplantilla($pokePlantilla);
            $entityManager->persist($pokePlantilla);
            $entityManager->persist($pokemon);
        }
        $entityManager->flush();
        return $this->render('pokemons/index.html.twig', [
            'pokemons' => $pokemonsRepository->findAll(),
        ]);
    
    }

    #[Route('/{id}', name: 'app_pokemons_show', methods: ['GET'])]
    public function show(Pokemons $pokemon): Response
    {
        return $this->render('pokemons/show.html.twig', [
            'pokemon' => $pokemon,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_pokemons_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Pokemons $pokemon, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PokemonsType::class, $pokemon);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_pokemons_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pokemons/edit.html.twig', [
            'pokemon' => $pokemon,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pokemons_delete', methods: ['POST'])]
    public function delete(Request $request, Pokemons $pokemon, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$pokemon->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($pokemon);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_pokemons_index', [], Response::HTTP_SEE_OTHER);
    }




    
}
