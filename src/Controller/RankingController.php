<?php

namespace App\Controller;

use App\Entity\Ranking;
use App\Form\RankingType;
use App\Repository\RankingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/ranking')]
class RankingController extends AbstractController
{
    #[Route('/', name: 'app_ranking_index', methods: ['GET'])]
    public function index(RankingRepository $rankingRepository): Response
    {
        return $this->render('ranking/index.html.twig', [
            'rankings' => $rankingRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_ranking_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $ranking = new Ranking();
        $form = $this->createForm(RankingType::class, $ranking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($ranking);
            $entityManager->flush();

            return $this->redirectToRoute('app_ranking_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ranking/new.html.twig', [
            'ranking' => $ranking,
            'form' => $form,
        ]);
    }

    // ... aquí irían edit y delete, pero con el "new" ya te vale para empezar
}
