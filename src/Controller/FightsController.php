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

        // Obtener los Pokémon del usuario
        $userPokemons = $entityManager->getRepository(Pokemons::class)->findBy(['user' => $user]);

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

        if ($request->isMethod('POST')) {
            $selectedPokemon = $entityManager->getRepository(Pokemons::class)->find($request->request->get('pokeuser_id'));

            if (!$selectedPokemon || $selectedPokemon->getUser() !== $user) {
                throw $this->createAccessDeniedException('Selección de Pokémon inválida.');
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
            $fight->setPokenemy($randomPokemon);
            $fight->setResult($resultado);
            
            $entityManager->persist($fight);
            $entityManager->flush();

            return $this->redirectToRoute('app_main');
        }

        return $this->render('fights/new.html.twig', [
            'user_pokemons' => $userPokemons,
            'random_pokemon' => $randomPokemon,
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
}
