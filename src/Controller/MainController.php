<?php

namespace App\Controller;

use App\Entity\Pokemons;
use App\Repository\PokemonsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\PvpChallenge;

final class MainController extends AbstractController

{
    #[Route('/', name: 'app_main')]
    public function index(PokemonsRepository $pokemonsRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $pokemons = $user ? $pokemonsRepository->findBy(['user' => $user]) : [];
        
        // Obtener el número de desafíos pendientes
        $pendingChallenges = 0;
        if ($user) {
            $pendingChallenges = $entityManager->getRepository(PvpChallenge::class)
                ->count(['targetTrainer' => $user, 'status' => 'pending']);
        }

        return $this->render('main/index.html.twig', [
            'pokemons' => $pokemons,
            'pending_challenges' => $pendingChallenges
        ]);
    }

}
