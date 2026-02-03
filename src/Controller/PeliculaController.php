<?php

namespace App\Controller;

use App\Entity\Pelicula;
use App\Entity\Valoracion;
use App\Form\PeliculaType;
use App\Form\ValoracionType;
use App\Repository\PeliculaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route; // <--- HEMOS CAMBIADO ANNOTATION POR ATTRIBUTE

#[Route('/pelicula')]
class PeliculaController extends AbstractController
{
    #[Route('/', name: 'app_pelicula_index', methods: ['GET'])]
    public function index(PeliculaRepository $peliculaRepository): Response
    {
        return $this->render('pelicula/index.html.twig', [
            // El nombre de la izquierda es el que usas en el HTML {{ for pelicula in peliculas }}
            'peliculas' => $peliculaRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_pelicula_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $pelicula = new Pelicula();
        $form = $this->createForm(PeliculaType::class, $pelicula);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($pelicula);
            $entityManager->flush();

            return $this->redirectToRoute('app_pelicula_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pelicula/new.html.twig', [
            'pelicula' => $pelicula,
            'form' => $form,
        ]);
    }

    // --- ESTA ES LA RUTA QUE TE FALTA Y DABA EL ERROR ---
    #[Route('/{id}', name: 'app_pelicula_show', methods: ['GET', 'POST'])]
    public function show(Request $request, Pelicula $pelicula, EntityManagerInterface $entityManager): Response
    {
        // 1. Creamos una valoración vacía
        $valoracion = new Valoracion();
        $form = $this->createForm(ValoracionType::class, $valoracion);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Si no está logueado, fuera
            $user = $this->getUser();
            if (!$user) {
                return $this->redirectToRoute('app_login');
            }

            // Rellenamos datos automáticos
            $valoracion->setUsuario($user);
            $valoracion->setPelicula($pelicula);

            $entityManager->persist($valoracion);
            $entityManager->flush();

            return $this->redirectToRoute('app_pelicula_show', ['id' => $pelicula->getId()]);
        }

        return $this->render('pelicula/show.html.twig', [
            'pelicula' => $pelicula,
            'form' => $form->createView(),
        ]);
    }
    // ----------------------------------------------------

    #[Route('/{id}/edit', name: 'app_pelicula_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Pelicula $pelicula, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PeliculaType::class, $pelicula);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_pelicula_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pelicula/edit.html.twig', [
            'pelicula' => $pelicula,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pelicula_delete', methods: ['POST'])]
    public function delete(Request $request, Pelicula $pelicula, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$pelicula->getId(), $request->request->get('_token'))) {
            $entityManager->remove($pelicula);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_pelicula_index', [], Response::HTTP_SEE_OTHER);
    }
}
