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

        // Obteners los Pokémons del usuario
        $userPokemons = $entityManager->getRepository(Pokemons::class)->findBy(['user' => $user]);
        
        if (empty($userPokemons)) {
            $this->addFlash('error', 'Necesitas tener al menos un Pokémon para luchar.');
            return $this->redirectToRoute('app_main');
        }

        // Si es POST, procesar el combate
        if ($request->isMethod('POST')) {
            $selectedPokemonId = $request->request->get('pokeuser_id');
            $selectedPokemon = $entityManager->getRepository(Pokemons::class)->find($selectedPokemonId);

            if (!$selectedPokemon || $selectedPokemon->getUser() !== $user) {
                throw $this->createAccessDeniedException('Selección de Pokémon inválida.');
            }

            // Obtener el Pokémon enemigo de la sesión
            $enemyPokemonId = $request->getSession()->get('enemy_pokemon_id');
            $randomPokemon = $entityManager->getRepository(Pokemons::class)->find($enemyPokemonId);

            if (!$randomPokemon) {
                $this->addFlash('error', 'Error en el combate: Pokémon enemigo no encontrado.');
                return $this->redirectToRoute('app_fights_new');
            }

            // Calcular poder de combate
            $poderUsuario = $selectedPokemon->getLevel() * $selectedPokemon->getStrength();
            $poderEnemigo = $randomPokemon->getLevel() * $randomPokemon->getStrength();

            // Determinar ganador
            $resultado = $poderUsuario > $poderEnemigo ? 1 : ($poderUsuario < $poderEnemigo ? 2 : 0);

            if ($resultado === 1) {
                $selectedPokemon->setLevel($selectedPokemon->getLevel() + 1);
                $this->addFlash('success', '¡Has ganado el combate!');
            } elseif ($resultado === 2) {
                $this->addFlash('error', '¡Has perdido el combate!');
            } else {
                $this->addFlash('info', '¡El combate ha terminado en empate!');
            }

            // Guardar el combate
            $fight = new Fights();
            $fight->setPokeuser($selectedPokemon);
            $fight->setPokenemy($randomPokemon);
            $fight->setResult($resultado);

            $entityManager->persist($fight);
            $entityManager->flush();

            // Limpiar la sesión
            $request->getSession()->remove('enemy_pokemon_id');

            return $this->redirectToRoute('app_main');
        }

        // Si es GET, generar nuevo Pokémon enemigo
        try {
            // Generar Pokémon enemigo aleatorio
            $randomPokemonId = random_int(1, 151);
            $pokemonApiUrl = "https://pokeapi.co/api/v2/pokemon/{$randomPokemonId}";
            $pokemonData = json_decode(file_get_contents($pokemonApiUrl), true);

            // Buscar o crear plantilla
            $pokePlantilla = $entityManager->getRepository(Pokeplantilla::class)
                ->findOneBy(['name' => $pokemonData['name']]);
            
            if (!$pokePlantilla) {
                $pokePlantilla = new Pokeplantilla();
                $pokePlantilla->setName($pokemonData['name']);
                $pokePlantilla->setType($pokemonData['types'][0]['type']['name']);
                $pokePlantilla->setImg("https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/dream-world/{$randomPokemonId}.svg");
                $entityManager->persist($pokePlantilla);
            }

            // Crear Pokémon enemigo
            $randomPokemon = new Pokemons();
            $randomPokemon->setLevel(random_int(1, 5));
            $randomPokemon->setStrength(random_int(8, 12));
            $randomPokemon->setUser(null);
            $randomPokemon->setPokeplantilla($pokePlantilla);
            
            $entityManager->persist($randomPokemon);
            $entityManager->flush();

            // Guardar ID en sesión
            $request->getSession()->set('enemy_pokemon_id', $randomPokemon->getId());

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