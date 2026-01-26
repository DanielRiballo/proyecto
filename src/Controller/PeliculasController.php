<?php

namespace App\Controller;

use App\Repository\PeliculaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PeliculasController extends AbstractController
{
    #[Route('/peliculas', name: 'app_peliculas')]
    public function index(PeliculaRepository $peliculaRepository): Response
    {
        $peliculas = $peliculaRepository->findAll();

        return $this->render('peliculas/index.html.twig', [
            'peliculas' => $peliculas,
        ]);
    }
}
