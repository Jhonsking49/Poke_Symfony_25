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
use Symfony\Component\Validator\Constraints\Length;

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

    #[Route('/pokemon/train/{id}', name: 'app_pokemon_train')]
    public function train(int $id, EntityManagerInterface $entityManager): Response
    {
        $pokemon = $entityManager->getRepository(Pokemons::class)->find($id);
        
        if ($pokemon->getState() === 0) {
            $this->addFlash('error', 'Tu Pokémon está malherido y no puede entrenar.');
            return $this->redirectToRoute('app_main');
        }
        
        $pokemon->setStrength($pokemon->getStrength() + 10);
        $entityManager->persist($pokemon);
        $entityManager->flush();

        return $this->redirectToRoute('app_main');
    }

    #[Route('/available-pokemon', name: 'app_pokemons_available', methods: ['GET'])]
    public function availablePokemon(
        Request $request,
        EntityManagerInterface $entityManager, 
        PokeplantillaRepository $pokeplantillaRepository
    ): Response
    {
        try {
            // Generar un ID aleatorio entre 1 y 151
            $randomPokemonId = random_int(1, 151);
            $pokemonApiUrl = "https://pokeapi.co/api/v2/pokemon/{$randomPokemonId}";
            $pokemonData = json_decode(file_get_contents($pokemonApiUrl), true);
            
            // Buscar si ya existe una plantilla para este Pokémon
            $pokePlantilla = $pokeplantillaRepository->findOneBy(['name' => $pokemonData['name']]);
            
            // Si no existe la plantilla, la creamos
            if (!$pokePlantilla) {
                $pokePlantilla = new Pokeplantilla();
                $pokePlantilla->setName($pokemonData['name']);
                $pokePlantilla->setType($pokemonData['types'][0]['type']['name']);
                $entityManager->persist($pokePlantilla);
                $entityManager->flush();
            }

            // Crear nuevo Pokémon temporal
            $pokemon = new Pokemons();
            $pokemon->setLevel(1);
            $pokemon->setStrength(10);
            $pokemon->setPokeplantilla($pokePlantilla);

            // Guardar datos en la sesión
            $request->getSession()->set('temp_pokemon', [
                'plantilla_id' => $pokePlantilla->getId(),
                'level' => $pokemon->getLevel(),
                'strength' => $pokemon->getStrength(),
                'img' => $pokePlantilla->getImg()
            ]);

            return $this->render('pokemons/available.html.twig', [
                'pokemon' => $pokemon,
                'random_id' => $randomPokemonId // Pasamos el ID aleatorio a la vista
            ]);

        } catch (\Exception $e) {
            $this->addFlash('error', 'Ha ocurrido un error al obtener el Pokémon.');
            return $this->redirectToRoute('app_main');
        }
    }

    #[Route('/mis-pokemons', name: 'app_pokemons_mis_pokemons', methods: ['GET'])]
    public function misPokemons(PokemonsRepository $pokemonsRepository): Response
    {
        return $this->render('pokemons/mis-pokemons.html.twig', [
            'pokemons' => $pokemonsRepository->findBy(['user' => $this->getUser()]),
        ]);
    }

    #[Route('/pokemon/capture/{random_id}', name: 'app_pokemon_capture', methods: ['GET'])]
    public function capture(
        int $random_id,
        Request $request, 
        EntityManagerInterface $entityManager
    ): Response
    {
        if (!$this->getUser()) {
            throw $this->createAccessDeniedException('Debes estar logueado para capturar pokémons.');
        }

        try {
            // Recuperar datos de la sesión
            $tempPokemon = $request->getSession()->get('temp_pokemon');
            if (!$tempPokemon) {
                throw new \Exception('No hay ningún Pokémon disponible para capturar.');
            }

            $pokePlantilla = $entityManager->getRepository(Pokeplantilla::class)->find($tempPokemon['plantilla_id']);
            if (!$pokePlantilla) {
                throw new \Exception('Plantilla de Pokémon no encontrada.');
            }

            // Implementar probabilidad de captura del 60%
            $probabilidadCaptura = random_int(1, 100);
            
            if ($probabilidadCaptura <= 60) {
                // Captura exitosa
                $pokemon = new Pokemons();
                $pokemon->setLevel($tempPokemon['level']);
                $pokemon->setStrength($tempPokemon['strength']);
                $pokemon->setUser($this->getUser());
                $pokemon->setPokeplantilla($pokePlantilla);
                $pokemon->setState(1);
                
                $entityManager->persist($pokemon);
                $entityManager->flush();
                
                $this->addFlash('success', '¡Has capturado el Pokémon con éxito!');
            } else {
                // Captura fallida
                $this->addFlash('error', 'El Pokémon se ha escapado...');
            }

            // Limpiar la sesión
            $request->getSession()->remove('temp_pokemon');

            return $this->redirectToRoute('app_pokemons_available');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Ha ocurrido un error durante la captura: ' . $e->getMessage());
            return $this->redirectToRoute('app_main');
        }
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
