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
            throw $this->createAccessDeniedException('You must be logged in to fight.');
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
            throw $this->createNotFoundException('No enemy Pokémon found.');
        }

        // Obtener un Pokémon aleatorio
        $randomOffset = random_int(0, $totalPokemons - 1);
        $randomPokemon = $entityManager->createQueryBuilder()
            ->select('p')
            ->from(Pokemons::class, 'p')
            ->setMaxResults(1)
            ->setFirstResult($randomOffset)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$randomPokemon) {
            throw $this->createNotFoundException('No enemy Pokémon found.');
        }

        if ($request->isMethod('POST')) {
            $selectedPokemon = $entityManager->getRepository(Pokemons::class)->find($request->request->get('pokeuser_id'));

            if (!$selectedPokemon || $selectedPokemon->getUser() !== $user) {
                throw $this->createAccessDeniedException('Invalid Pokémon selection.');
            }

            $fight = new Fights();
            $fight->setPokeuser($selectedPokemon);
            $fight->setPokenemy($randomPokemon);
            $entityManager->persist($fight);
            $entityManager->flush();

            return $this->redirectToRoute('app_fights_index');
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
}
