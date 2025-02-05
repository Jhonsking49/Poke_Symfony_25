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
        $fight = new Fights();
        $form = $this->createForm(FightsType::class, $fight);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($fight);
            $entityManager->flush();

            return $this->redirectToRoute('app_fights_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('fights/new.html.twig', [
            'fight' => $fight,
            'form' => $form,
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
