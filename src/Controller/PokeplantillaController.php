<?php

namespace App\Controller;

use App\Entity\Pokeplantilla;
use App\Form\PokeplantillaType;
use App\Repository\PokeplantillaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pokeplantilla')]
final class PokeplantillaController extends AbstractController
{
    #[Route(name: 'app_pokeplantilla_index', methods: ['GET'])]
    public function index(PokeplantillaRepository $pokeplantillaRepository): Response
    {
        return $this->render('pokeplantilla/index.html.twig', [
            'pokeplantillas' => $pokeplantillaRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_pokeplantilla_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $pokeplantilla = new Pokeplantilla();
        $form = $this->createForm(PokeplantillaType::class, $pokeplantilla);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($pokeplantilla);
            $entityManager->flush();

            return $this->redirectToRoute('app_pokeplantilla_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pokeplantilla/new.html.twig', [
            'pokeplantilla' => $pokeplantilla,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pokeplantilla_show', methods: ['GET'])]
    public function show(Pokeplantilla $pokeplantilla): Response
    {
        return $this->render('pokeplantilla/show.html.twig', [
            'pokeplantilla' => $pokeplantilla,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_pokeplantilla_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Pokeplantilla $pokeplantilla, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PokeplantillaType::class, $pokeplantilla);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_pokeplantilla_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pokeplantilla/edit.html.twig', [
            'pokeplantilla' => $pokeplantilla,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pokeplantilla_delete', methods: ['POST'])]
    public function delete(Request $request, Pokeplantilla $pokeplantilla, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$pokeplantilla->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($pokeplantilla);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_pokeplantilla_index', [], Response::HTTP_SEE_OTHER);
    }
}
