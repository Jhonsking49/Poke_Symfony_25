<?php

namespace App\Controller;

use App\Entity\Fights;
use App\Form\FightsType;
use App\Repository\FightsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Pokemons;
use App\Entity\Pokeplantilla;

#[Route('/fights')]
final class FightsController extends AbstractController
{
    #[Route('/my-fights', name: 'app_fights_user_history', methods: ['GET'])]
    public function userFights(FightsRepository $fightsRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Debes estar logueado para ver tu historial.');
        }

        $fights = $fightsRepository->createQueryBuilder('f')
            ->join('f.pokeuser', 'p')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('f.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('fights/user_history.html.twig', [
            'fights' => $fights,
        ]);
    }

    #[Route(name: 'app_fights_index', methods: ['GET'])]
    public function index(FightsRepository $fightsRepository): Response
    {
        return $this->render('fights/index.html.twig', [
            'fights' => $fightsRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_fights_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Debes estar logueado para luchar.');
        }

        // Obtener los Pokémons del usuario
        $userPokemons = $entityManager->getRepository(Pokemons::class)->findBy(['user' => $user]);
        
        if (empty($userPokemons)) {
            $this->addFlash('error', 'Necesitas tener al menos un Pokémon para luchar.');
            return $this->redirectToRoute('app_main');
        }

        // Si es POST, procesar el combate
        if ($request->isMethod('POST')) {
            $selectedPokemonId = $request->request->get('pokeuser_id');
            $enemyPokemonId = $request->request->get('pokenemy_id');
            
            $selectedPokemon = $entityManager->getRepository(Pokemons::class)->find($selectedPokemonId);
            $enemyPokemon = $entityManager->getRepository(Pokemons::class)->find($enemyPokemonId);

            if (!$selectedPokemon || !$enemyPokemon) {
                $this->addFlash('error', 'Error: Pokémon no encontrado.');
                return $this->redirectToRoute('app_fights_new');
            }

            if ($selectedPokemon->getState() === 0) {
                $this->addFlash('error', 'Este Pokémon está malherido y no puede luchar.');
                return $this->redirectToRoute('app_fights_new');
            }

            // Calcular resultado
            $poderUsuario = $selectedPokemon->getLevel() * $selectedPokemon->getStrength();
            $poderEnemigo = $enemyPokemon->getLevel() * $enemyPokemon->getStrength();
            
            $resultado = $poderUsuario > $poderEnemigo ? 1 : ($poderUsuario < $poderEnemigo ? 2 : 0);

            // Crear y guardar el combate
            $fight = new Fights();
            $fight->setPokeuser($selectedPokemon);
            $fight->setPokenemy($enemyPokemon);
            $fight->setResult($resultado);

            if ($resultado === 1) {
                $selectedPokemon->setLevel($selectedPokemon->getLevel() + 1);
                $this->addFlash('success', '¡Victoria! Tu Pokémon ha subido de nivel.');
            } else {
                $selectedPokemon->setState(0);
                $this->addFlash('error', 'Has perdido el combate. Tu Pokémon está malherido.');
            }

            $entityManager->persist($fight);
            $entityManager->flush();

            return $this->redirectToRoute('app_fights_index');
        }

        // Generar Pokémon enemigo aleatorio
        try {
            $randomPokemonId = random_int(1, 151);
            $pokemonApiUrl = "https://pokeapi.co/api/v2/pokemon/{$randomPokemonId}";
            $pokemonData = json_decode(file_get_contents($pokemonApiUrl), true);

            $pokePlantilla = $entityManager->getRepository(Pokeplantilla::class)
                ->findOneBy(['name' => $pokemonData['name']]);

            if (!$pokePlantilla) {
                $pokePlantilla = new Pokeplantilla();
                $pokePlantilla->setName($pokemonData['name']);
                $pokePlantilla->setType($pokemonData['types'][0]['type']['name']);
                $pokePlantilla->setImg("https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/dream-world/{$randomPokemonId}.svg");
                $entityManager->persist($pokePlantilla);
            }

            $randomPokemon = new Pokemons();
            $randomPokemon->setLevel(random_int(1, 5));
            $randomPokemon->setStrength(random_int(8, 12));
            $randomPokemon->setState(1);
            $randomPokemon->setPokeplantilla($pokePlantilla);
            
            $entityManager->persist($randomPokemon);
            $entityManager->flush();

            return $this->render('fights/new.html.twig', [
                'user_pokemons' => $userPokemons,
                'random_pokemon' => $randomPokemon,
            ]);

        } catch (\Exception $e) {
            $this->addFlash('error', 'Error al generar el Pokémon enemigo.');
            return $this->redirectToRoute('app_main');
        }
    }


    #[Route('/{id}', name: 'app_fights_show', methods: ['GET'])]
    public function show(Fights $fight): Response
    {
        return $this->render('fights/show.html.twig', [
            'fight' => $fight,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_fights_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Fights $fight, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FightsType::class, $fight);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_fights_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('fights/edit.html.twig', [
            'fight' => $fight,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_fights_delete', methods: ['POST'])]
    public function delete(Request $request, Fights $fight, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$fight->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($fight);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_fights_index', [], Response::HTTP_SEE_OTHER);
    }
}