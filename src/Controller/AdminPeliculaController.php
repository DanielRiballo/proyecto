<?php

namespace App\Controller;

use App\Entity\Pelicula;
use App\Form\PeliculaType;
use App\Repository\PeliculaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/pelicula')]
final class AdminPeliculaController extends AbstractController
{
    #[Route(name: 'app_admin_pelicula_index', methods: ['GET'])]
    public function index(PeliculaRepository $peliculaRepository): Response
    {
        return $this->render('admin_pelicula/index.html.twig', [
            'pelicula' => $peliculaRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_pelicula_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $pelicula = new Pelicula();
        $form = $this->createForm(PeliculaType::class, $pelicula);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($pelicula);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_pelicula_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin_pelicula/new.html.twig', [
            'pelicula' => $pelicula,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_pelicula_show', methods: ['GET'])]
    public function show(Pelicula $pelicula): Response
    {
        return $this->render('admin_pelicula/show.html.twig', [
            'pelicula' => $pelicula,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_pelicula_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Pelicula $pelicula, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PeliculaType::class, $pelicula);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_pelicula_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin_pelicula/edit.html.twig', [
            'pelicula' => $pelicula,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_pelicula_delete', methods: ['POST'])]
    public function delete(Request $request, Pelicula $pelicula, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$pelicula->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($pelicula);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_pelicula_index', [], Response::HTTP_SEE_OTHER);
    }
}
