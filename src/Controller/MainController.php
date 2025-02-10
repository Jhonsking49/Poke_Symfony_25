<?php

namespace App\Controller;

use App\Entity\Pokemons;
use App\Repository\PokemonsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MainController extends AbstractController

{
    #[Route('/', name: 'app_main')]
    public function index(PokemonsRepository $pokemonsRepository): Response
    {
        $pokemons = $pokemonsRepository->findAllByUserIdNull();
        return $this->render('main/index.html.twig', [

            'controller_name' => 'MainController',
            'pokemons' => $pokemons
        ]);
    }

}
