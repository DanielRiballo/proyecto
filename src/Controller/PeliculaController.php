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
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/pelicula')]
class PeliculaController extends AbstractController
{
    #[Route('/ranking/ver/{genero}', name: 'app_admin_ranking_genero', defaults: ['genero' => null], methods: ['GET'])]
    public function visualizarRanking(PeliculaRepository $peliculaRepository, Request $request, ?string $genero = null): Response
    {
        $query = $request->query->get('q');

        $qb = $peliculaRepository->createQueryBuilder('p')
            ->leftJoin('p.valoraciones', 'v')
            ->addSelect('AVG(v.estrellas) as HIDDEN media_estrellas')
            ->groupBy('p.id')
            ->orderBy('media_estrellas', 'DESC');

        if ($genero && $genero !== '') {
            $qb->andWhere('p.genre = :g')
                ->setParameter('g', $genero);
        }

        if ($query) {
            $qb->andWhere('p.titulo LIKE :q')
                ->setParameter('q', '%'.$query.'%');
        }

        return $this->render('admin_pelicula/ranking.html.twig', [
            'peliculas' => $qb->getQuery()->getResult(),
            'categoria_actual' => $genero,
            'texto_busqueda' => $query
        ]);
    }

    #[Route('/detalle/peli/{id}', name: 'app_pelicula_show', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function show(Request $request, Pelicula $pelicula, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $valoracionExistente = null;

        if ($user) {
            foreach ($pelicula->getValoraciones() as $v) {
                if ($v->getUsuario() === $user) {
                    $valoracionExistente = $v;
                    break;
                }
            }
        }

        $valoracion = $valoracionExistente ?? new Valoracion();
        $form = $this->createForm(ValoracionType::class, $valoracion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$user) return $this->redirectToRoute('app_login');

            if (!$valoracionExistente) {
                $valoracion->setUsuario($user);
                $valoracion->setPelicula($pelicula);
                $entityManager->persist($valoracion);
            }

            $entityManager->flush();
            $this->addFlash('success', '¡Valoración guardada!');
            return $this->redirectToRoute('app_pelicula_show', ['id' => $pelicula->getId()]);
        }

        return $this->render('pelicula/show.html.twig', [
            'pelicula' => $pelicula,
            'form' => $form->createView(),
            'yaHaComentado' => ($valoracionExistente !== null)
        ]);
    }

    #[Route('/lista/todas', name: 'app_pelicula_index', methods: ['GET'])]
    public function index(PeliculaRepository $peliculaRepository): Response
    {
        return $this->render('pelicula/index.html.twig', [
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
            'form' => $form->createView(),
        ]);
    }

    #[Route('/editar/{id}', name: 'app_pelicula_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
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
            'form' => $form->createView(),
        ]);
    }

    #[Route('/valoracion/borrar/{id}', name: 'app_valoracion_delete', methods: ['POST'])]
    public function deleteValoracion(Request $request, Valoracion $valoracion, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser() !== $valoracion->getUsuario()) {
            throw $this->createAccessDeniedException('Acceso denegado.');
        }

        if ($this->isCsrfTokenValid('delete'.$valoracion->getId(), $request->request->get('_token'))) {
            $peliculaId = $valoracion->getPelicula()->getId();
            $entityManager->remove($valoracion);
            $entityManager->flush();
            $this->addFlash('success', 'Comentario eliminado.');
        }

        return $this->redirectToRoute('app_pelicula_show', ['id' => $peliculaId]);
    }

    #[Route('/borrar/peli/{id}', name: 'app_pelicula_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Pelicula $pelicula, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$pelicula->getId(), $request->request->get('_token'))) {
            $entityManager->remove($pelicula);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_pelicula_index', [], Response::HTTP_SEE_OTHER);
    }
}
