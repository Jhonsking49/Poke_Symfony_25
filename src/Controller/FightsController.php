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
use App\Repository\PokemonsRepository;

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
    public function new(Request $request, EntityManagerInterface $entityManager, PokemonsRepository $pokemonsRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Debes estar logueado para luchar.');
        }

        // Obtener los Pokémon del usuario
        $userPokemons = $entityManager->getRepository(Pokemons::class)->findBy(['user' => $user]);

        // Recuperar sesión
        $session = $request->getSession();

        if (!$session->has('pokenemy_id')) {
            // Obtener el total de Pokémon en la base de datos
            $totalPokemons = $entityManager->createQueryBuilder()
                ->select('COUNT(p.id)')
                ->from(Pokemons::class, 'p')
                ->where('p.user IS NULL')
                ->getQuery()
                ->getSingleScalarResult();

            if ($totalPokemons == 0) {
                throw $this->createNotFoundException('No se encontraron Pokémon enemigos.');
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

            if (!$randomPokemon) {
                throw $this->createNotFoundException('No se encontraron Pokémon enemigos.');
            }

            // Guardar en la sesión
            $session->set('pokenemy_id', $randomPokemon->getId());
        } else {
            // Recuperar Pokémon de la sesión
            $randomPokemon = $entityManager->getRepository(Pokemons::class)->find($session->get('pokenemy_id'));
        }

        if ($request->isMethod('POST')) {
            $selectedPokemon = $entityManager->getRepository(Pokemons::class)->find($request->request->get('pokeuser_id'));

            if (!$selectedPokemon || $selectedPokemon->getUser() !== $user) {
                throw $this->createAccessDeniedException('Selección de Pokémon inválida.');
            }

            // Recuperar el Pokémon enemigo de la sesión
            $pokenemyId = $session->get('pokenemy_id');
            $randomPokemon = $entityManager->getRepository(Pokemons::class)->find($pokenemyId);

            if (!$randomPokemon) {
                throw new \Exception('No se pudo recuperar el Pokémon enemigo de la sesión.');
            }

            // Calcular poder de combate
            $poderUsuario = $selectedPokemon->getLevel() * $selectedPokemon->getStrength();
            $poderEnemigo = $randomPokemon->getLevel() * $randomPokemon->getStrength();

            // Determinar ganador
            $resultado = $poderUsuario > $poderEnemigo ? 1 : ($poderUsuario < $poderEnemigo ? 2 : 0);

            // Subir nivel al ganador y mostrar mensaje apropiado
            if ($resultado === 1) {
                $selectedPokemon->setLevel($selectedPokemon->getLevel() + 1);
                $this->addFlash('success', sprintf(
                    '¡Tu %s ha ganado el combate contra %s!',
                    $selectedPokemon->getPokeplantilla()->getName(),
                    $randomPokemon->getPokeplantilla()->getName()
                ));
            
                $entityManager->persist($selectedPokemon);
                $entityManager->flush();
                return $this->redirectToRoute('app_fights_winneroptions', [
                    'winner_pokemon_id' => $selectedPokemon->getId(),
                    'enemy_pokemon_id' => $randomPokemon->getId(),
                ]);
            } elseif ($resultado === 2) {
                $pokemonsRepository->pokedeadalive($selectedPokemon);
                $this->addFlash('error', sprintf(
                    '¡Tu %s ha perdido el combate contra %s!',
                    $selectedPokemon->getPokeplantilla()->getName(),
                    $randomPokemon->getPokeplantilla()->getName()
                ));
            } else {
                $this->addFlash('info', sprintf(
                    '¡El combate entre %s y %s ha terminado en empate!',
                    $selectedPokemon->getPokeplantilla()->getName(),
                    $randomPokemon->getPokeplantilla()->getName()
                ));
            }

            // Guardar el combate en el historial
            $fight = new Fights();
            $fight->setPokeuser($selectedPokemon);
            $fight->setPokenemy($randomPokemon);
            $fight->setResult($resultado);

            $entityManager->persist($fight);
            $entityManager->flush();

            // Limpiar la sesión después del combate
            if($resultado != 1) {
                $session->remove('pokenemy_id');
            }

            return $this->redirectToRoute('app_main');
        }

        return $this->render('fights/new.html.twig', [
            'user_pokemons' => $userPokemons,
            'random_pokemon' => $randomPokemon,
        ]);
    }

    #[Route('/winneroptions', name: 'app_fights_winneroptions', methods: ['GET', 'POST'])]
    public function winneroptions(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Debes estar logueado para acceder a esta opción.');
        }

        // Obtener el Pokémon ganador de la última batalla (podrías almacenarlo en sesión o como parámetro)
        $selectedPokemonId = $request->query->get('winner_pokemon_id');
        $selectedPokemon = $entityManager->getRepository(Pokemons::class)->find($selectedPokemonId);

        if (!$selectedPokemon || $selectedPokemon->getUser() !== $user) {
            throw $this->createAccessDeniedException('Pokémon inválido.');
        }

        // Obtener Pokémon malheridos del usuario (state = 1)
        $injuredPokemons = $entityManager->getRepository(Pokemons::class)->findInjured($user->getId(), $selectedPokemon);

        // Lanzar excepción si no hay Pokémon malheridos
        if (empty($injuredPokemons)) {
            $injuredPokemons = array();
            //throw $this->createNotFoundException('No hay Pokémon malheridos para revivir.');
        }

        // Obtener el Pokémon enemigo vencido
        $enemyPokemonId = $request->query->get('enemy_pokemon_id');
        $enemyPokemon = $entityManager->getRepository(Pokemons::class)->findBy(['id' => $enemyPokemonId]);

        if (!$enemyPokemon) {
            throw $this->createNotFoundException('Pokémon enemigo no encontrado.');
        }

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');

            if ($action === 'levelup') {
                // Opción 1: Subir de nivel al Pokémon ganador
                $selectedPokemon->setLevel($selectedPokemon->getLevel() + 1);
                $this->addFlash('success', sprintf(
                    '¡Tu %s ha subido al nivel %d!',
                    $selectedPokemon->getPokeplantilla()->getName(),
                    $selectedPokemon->getLevel()
                ));

            } elseif ($action === 'capture') {
                // Opción 2: Intentar capturar al Pokémon vencido (60% de probabilidad)
                $chance = rand(1, 100);
                if ($chance <= 60) {
                    $enemyPokemon->setUser($user);
                    $this->addFlash('success', sprintf(
                        '¡Has capturado a %s!',
                        $enemyPokemon->getPokeplantilla()->getName()
                    ));
                } else {
                    $this->addFlash('error', sprintf(
                        'No has podido capturar a %s.',
                        $enemyPokemon->getPokeplantilla()->getName()
                    ));
                }

            } elseif ($action === 'revive') {
                // Opción 3: Revivir un Pokémon malherido
                $revivePokemonId = $request->request->get('revive_pokemon_id');
                $revivePokemon = $entityManager->getRepository(Pokemons::class)->find($revivePokemonId);

                if ($revivePokemon && $revivePokemon->getUser() === $user && $revivePokemon->getState() === 1) {
                    $revivePokemon->setState(0);
                    $this->addFlash('success', sprintf(
                        '¡Tu Pokémon %s ha sido revivido!',
                        $revivePokemon->getPokeplantilla()->getName()
                    ));
                } else {
                    $this->addFlash('error', 'No puedes revivir este Pokémon.');
                }
            }

            // Guardar los cambios
            $entityManager->persist($selectedPokemon);
            $entityManager->persist($enemyPokemon);
            $entityManager->flush();

            return $this->redirectToRoute('app_main');
        }

        return $this->render('fights/winneroptions.html.twig', [
            'injured_pokemons' => $injuredPokemons,
            'selected_pokemon' => $selectedPokemon,
            'enemy_pokemon' => $enemyPokemon,
        ]);
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
