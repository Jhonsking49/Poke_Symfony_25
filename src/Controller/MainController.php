<?php

namespace App\Controller;

use App\Entity\Pokemons;
use App\Repository\PokemonsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MainController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    public function index(PokemonsRepository $pokemonsRepository): Response
    {

        return $this->render('main/index.html.twig', [
            'controller_name' => 'MainController',
            'pokemons' => $pokemonsRepository->findBy(['user' => $this->getUser()->getId()]),
        ]);
    }

    #[Route('/{id}', name: 'app_entrenar', methods: ['GET'])]
    public function entrenar(int $id, Pokemons $pokemon, EntityManagerInterface $entityManager): Response
    {
        $pokemon = $entityManager->getRepository(Pokemons::class)->find($id);
        $pokemon->setStrength($pokemon->getStrength() + 10);
        $entityManager->persist($pokemon);
        $entityManager->flush();

        return $this->redirectToRoute('app_main');

    }

}
