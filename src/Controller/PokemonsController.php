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
            $pokemonApiUrl = "https://pokeapi.co/api/v2/pokemon/{$i}";
            $pokemonData = json_decode(file_get_contents($pokemonApiUrl), true);

            $pokemon = new Pokemons();
            $pokePlantilla = new Pokeplantilla();

            $pokePlantilla->setName($pokemonData['name']);
            $pokePlantilla->setType($pokemonData['types'][0]['type']['name']);
            $pokePlantilla->setImg("https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/dream-world/{$i}.svg");

            $pokemon->setLevel(1);
            $pokemon->setStrength(10);
            $pokemon->setUser(null);
            
            $pokemon->setPokeplantilla($pokePlantilla);
            $entityManager->persist($pokePlantilla);
            $entityManager->persist($pokemon);
        }
        $entityManager->flush();
        return $this->render('pokemons/index.html.twig', [
            'pokemons' => $pokemonsRepository->findAll(),
        ]);
    }

    #[Route('/pokemon/train/{id}', name: 'app_pokemon_train', methods: ['GET'])]
    public function entrenar(int $id, Pokemons $pokemon, EntityManagerInterface $entityManager): Response

    {
        $pokemon = $entityManager->getRepository(Pokemons::class)->find($id);
        $pokemon->setStrength($pokemon->getStrength() + 10);
        $entityManager->persist($pokemon);
        $entityManager->flush();

        return $this->redirectToRoute('app_main');
    }

    #[Route('/available-pokemon', name: 'app_pokemons_available', methods: ['GET'])]
    public function availablePokemon(EntityManagerInterface $entityManager): Response
    {
        // Obtener el total de Pokémon sin dueño
        $totalPokemons = $entityManager->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from(Pokemons::class, 'p')
            ->where('p.user IS NULL')
            ->getQuery()
            ->getSingleScalarResult();

        if ($totalPokemons == 0) {
            $this->addFlash('error', 'No hay pokémons disponibles para capturar.');
            return $this->redirectToRoute('app_main');
        }

        // Obtener un Pokémon aleatorio
        $randomOffset = random_int(0, $totalPokemons - 1);
        $randomPokemon = $entityManager->createQueryBuilder()
            ->select('p')
            ->from(Pokemons::class, 'p')
            ->where('p.user IS NULL')
            ->setMaxResults(1)
            ->setFirstResult($randomOffset)
            ->getQuery()
            ->getOneOrNullResult();

        return $this->render('pokemons/available.html.twig', [
            'pokemon' => $randomPokemon,
        ]);
    }

    #[Route('/mis-pokemons', name: 'app_pokemons_mis_pokemons', methods: ['GET'])]
    public function misPokemons(PokemonsRepository $pokemonsRepository): Response
    {
        return $this->render('pokemons/mis-pokemons.html.twig', [
            'pokemons' => $pokemonsRepository->findBy(['user' => $this->getUser()]),
        ]);
    }

    #[Route('/pokemon/capture/{id}', name: 'app_pokemon_capture', methods: ['GET'])]
    public function capture(int $id, EntityManagerInterface $entityManager, PokemonsRepository $pokemonsRepository): Response
    {
        if (!$this->getUser()) {
            throw $this->createAccessDeniedException('Debes estar logueado para capturar pokémons.');
        }

        $pokemon = $pokemonsRepository->find($id);
        
        if (!$pokemon) {
            throw $this->createNotFoundException('No se encontró el Pokémon.');
        }

        if ($pokemon->getUser() !== null) {
            throw $this->createAccessDeniedException('Este Pokémon ya tiene dueño.');
        }

        $resultado = $pokemonsRepository->intentarCapturarPokemon($pokemon, $this->getUser()->getId());
        
        $this->addFlash(
            $resultado['exito'] ? 'success' : 'error',
            $resultado['mensaje']
        );

        return $this->redirectToRoute('app_pokemons_available');
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
