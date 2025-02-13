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
    public function index(FightsRepository $fightsRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        // Obtener el número de desafíos pendientes
        $pendingChallenges = 0;
        if ($user) {
            $pendingChallenges = $entityManager->getRepository(PvpChallenge::class)
                ->count(['targetTrainer' => $user, 'status' => 'pending']);
        }

        return $this->render('fights/index.html.twig', [
            'fights' => $fightsRepository->findAll(),
            'pending_challenges' => $pendingChallenges
        ]);
    }

    #[Route('/new', name: 'app_fights_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, PokemonsRepository $pokemonsRepository): Response
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

            // Subir nivel al ganador y mostrar mensaje apropiado
            if ($resultado === 1) {
                $selectedPokemon->setLevel($selectedPokemon->getLevel() + 1);
                $this->addFlash('success', sprintf(
                    '¡Tu %s ha ganado el combate contra %s y ha subido al nivel %d!',
                    $selectedPokemon->getPokeplantilla()->getName(),
                    $randomPokemon->getPokeplantilla()->getName(),
                    $selectedPokemon->getLevel()
                ));
            } elseif ($resultado === 2) {
                $randomPokemon->setLevel($randomPokemon->getLevel() + 1);
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
            $fight->setPokenemy($enemyPokemon);
            $fight->setResult($resultado);

            if ($resultado === 1) {
                // Persistir el combate primero
                $entityManager->persist($fight);
                $entityManager->flush();

            // Limpiar la sesión después del combate
            $session->remove('pokenemy_id');

            return $this->redirectToRoute('app_main');
        }

            return $this->render('fights/new.html.twig', [
                'user_pokemons' => $userPokemons,
                'random_pokemon' => $randomPokemon,
            ]);

        } catch (\Exception $e) {
            $this->addFlash('error', 'Error al generar el Pokémon enemigo.');
            return $this->redirectToRoute('app_main');
        }
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

    #[Route('/fights/level-up/{fight_id}', name: 'app_fights_level_up')]
    public function levelUp(int $fight_id, EntityManagerInterface $entityManager): Response
    {
        $fight = $entityManager->getRepository(Fights::class)->find($fight_id);
        if (!$fight || $fight->getPokeuser()->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException('Combate no encontrado.');
        }

        $pokemon = $fight->getPokeuser();
        $pokemon->setLevel($pokemon->getLevel() + 1);
        $entityManager->flush();
        
        $this->addFlash('success', '¡Tu Pokémon ha subido de nivel!');
        return $this->redirectToRoute('app_fights_index');
    }

    #[Route('/fights/capture/{fight_id}', name: 'app_fights_capture')]
    public function captureDefeated(int $fight_id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $fight = $entityManager->getRepository(Fights::class)->find($fight_id);
        if (!$fight) {
            throw $this->createNotFoundException('Combate no encontrado.');
        }

        $defeatedPokemon = $request->getSession()->get('defeated_pokemon');
        if (!$defeatedPokemon) {
            $this->addFlash('error', 'No hay ningún Pokémon disponible para capturar.');
            return $this->redirectToRoute('app_fights_index');
        }

        $pokePlantilla = $entityManager->getRepository(Pokeplantilla::class)->find($defeatedPokemon['plantilla_id']);
        
        $newPokemon = new Pokemons();
        $newPokemon->setPokeplantilla($pokePlantilla);
        $newPokemon->setLevel($defeatedPokemon['level']);
        $newPokemon->setStrength($defeatedPokemon['strength']);
        $newPokemon->setState(1);
        $newPokemon->setUser($this->getUser());
        
        $entityManager->persist($newPokemon);
        $entityManager->flush();
        
        $request->getSession()->remove('defeated_pokemon');
        
        $this->addFlash('success', '¡Has capturado al Pokémon derrotado!');
        return $this->redirectToRoute('app_fights_index');
    }

    #[Route('/fights/heal/{fight_id}', name: 'app_fights_heal')]
    public function healOptions(int $fight_id, EntityManagerInterface $entityManager): Response
    {
        $fight = $entityManager->getRepository(Fights::class)->find($fight_id);
        if (!$fight) {
            throw $this->createNotFoundException('Combate no encontrado.');
        }

        $injuredPokemons = $entityManager->getRepository(Pokemons::class)
            ->findBy(['user' => $this->getUser(), 'state' => 0]);
        
        return $this->render('fights/heal_options.html.twig', [
            'injured_pokemons' => $injuredPokemons,
            'fight_id' => $fight_id
        ]);
    }

    #[Route('/fights/heal/{fight_id}/pokemon/{pokemon_id}', name: 'app_fights_heal_pokemon')]
    public function healPokemon(int $fight_id, int $pokemon_id, EntityManagerInterface $entityManager): Response
    {
        $pokemon = $entityManager->getRepository(Pokemons::class)->find($pokemon_id);
        
        if (!$pokemon || $pokemon->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException('Pokémon no encontrado.');
        }

        if ($pokemon->getState() !== 0) {
            $this->addFlash('error', 'Este Pokémon no está malherido.');
            return $this->redirectToRoute('app_fights_index');
        }

        $pokemon->setState(1);
        $entityManager->flush();
        
        $this->addFlash('success', '¡' . $pokemon->getPokeplantilla()->getName() . ' ha sido curado!');
        return $this->redirectToRoute('app_fights_index');
    }

    #[Route('/fights/pvp/new', name: 'app_fights_pvp_new', methods: ['GET', 'POST'])]
    public function newPvpFight(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Debes estar logueado para luchar.');
        }

        if ($request->isMethod('POST')) {
            $pokemonId = $request->request->get('pokeuser_id');
            $targetTrainerId = $request->request->get('target_trainer_id');
            
            $pokemon = $entityManager->getRepository(Pokemons::class)->find($pokemonId);
            $targetTrainer = $entityManager->getRepository(User::class)->find($targetTrainerId);
            
            if (!$pokemon || !$targetTrainer) {
                $this->addFlash('error', 'Error al procesar el desafío.');
                return $this->redirectToRoute('app_main');
            }

            $challenge = new PvpChallenge();
            $challenge->setChallenger($user);
            $challenge->setTargetTrainer($targetTrainer);
            $challenge->setChallengerPokemon($pokemon);
            $challenge->setStatus('pending');
            
            $entityManager->persist($challenge);
            $entityManager->flush();
            
            $this->addFlash('success', '¡Desafío enviado a ' . $targetTrainer->getUsername() . '!');
            return $this->redirectToRoute('app_main');
        }

        // Resto del código existente para GET
        $userPokemons = $entityManager->getRepository(Pokemons::class)
            ->findBy(['user' => $user, 'state' => 1]);

        $otherTrainers = $entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u != :currentUser')
            ->setParameter('currentUser', $user)
            ->getQuery()
            ->getResult();

        return $this->render('fights/pvp_new.html.twig', [
            'user_pokemons' => $userPokemons,
            'other_trainers' => $otherTrainers
        ]);
    }

    #[Route('/fights/accept-challenge/{id}', name: 'app_fights_accept_challenge', methods: ['GET', 'POST'])]
    public function acceptChallenge(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $challenge = $entityManager->getRepository(PvpChallenge::class)->find($id);
        if (!$challenge || $challenge->getTargetTrainer() !== $this->getUser()) {
            throw $this->createNotFoundException('Desafío no encontrado.');
        }

        if ($request->isMethod('POST')) {
            $defenderPokemonId = $request->request->get('defender_pokemon_id');
            $defenderPokemon = $entityManager->getRepository(Pokemons::class)->find($defenderPokemonId);
            
            if (!$defenderPokemon) {
                $this->addFlash('error', 'Pokémon no encontrado.');
                return $this->redirectToRoute('app_fights_pending');
            }

            // Calcular resultado
            $poderDesafiante = $challenge->getChallengerPokemon()->getLevel() * $challenge->getChallengerPokemon()->getStrength();
            $poderDefensor = $defenderPokemon->getLevel() * $defenderPokemon->getStrength();
            
            $resultado = $poderDesafiante > $poderDefensor ? 1 : ($poderDesafiante < $poderDefensor ? 2 : 0);

            // Crear el combate para el desafiante
            $fightDesafiante = new Fights();
            $fightDesafiante->setPokeuser($challenge->getChallengerPokemon());
            $fightDesafiante->setPokenemy($defenderPokemon);
            $fightDesafiante->setResult($resultado);

            // Crear el combate para el defensor (resultado invertido)
            $fightDefensor = new Fights();
            $fightDefensor->setPokeuser($defenderPokemon);
            $fightDefensor->setPokenemy($challenge->getChallengerPokemon());
            $fightDefensor->setResult($resultado === 1 ? 2 : ($resultado === 2 ? 1 : 0));

            // Actualizar estado de los pokémon según el resultado
            if ($resultado === 1) {
                $defenderPokemon->setState(0);
            } else {
                $challenge->getChallengerPokemon()->setState(0);
            }

            // Marcar el desafío como completado
            $challenge->setStatus('completed');

            // Persistir todos los cambios
            $entityManager->persist($fightDesafiante);
            $entityManager->persist($fightDefensor);
            $entityManager->flush();

            $this->addFlash('success', '¡Combate finalizado!');
            return $this->redirectToRoute('app_main');
        }

        return $this->render('fights/pvp_accept.html.twig', [
            'challenge' => $challenge,
            'user_pokemons' => $entityManager->getRepository(Pokemons::class)
                ->findBy(['user' => $this->getUser(), 'state' => 1])
        ]);
    }

    #[Route('/fights/pending', name: 'app_fights_pending')]
    public function pendingFights(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Debes estar logueado para ver los desafíos pendientes.');
        }

        // Obtener desafíos pendientes donde el usuario es el objetivo
        $pendingChallenges = $entityManager->getRepository(PvpChallenge::class)
            ->findBy(['targetTrainer' => $user, 'status' => 'pending']);

        return $this->render('fights/pending_challenges.html.twig', [
            'pending_challenges' => $pendingChallenges
        ]);
    }
}