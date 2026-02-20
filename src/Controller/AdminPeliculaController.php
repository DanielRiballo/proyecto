<?php

namespace App\Controller;

use App\Entity\Pelicula;
use App\Entity\Ranking;
use App\Entity\Valoracion;
use App\Form\PeliculaType;
use App\Form\RankingType;
use App\Repository\PeliculaRepository;
use App\Repository\RankingRepository;
use App\Repository\UsuarioRepository;
use App\Repository\ValoracionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class AdminPeliculaController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/dashboard', name: 'app_user_dashboard', methods: ['GET'])]
    public function userDashboard(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->render('home/index.html.twig');
    }

    #[Route('/estadisticas', name: 'app_rankings_estadisticas', methods: ['GET'])]
    public function estadisticas(RankingRepository $rankingRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $rankings = $rankingRepository->findAll();
        $datosEstadisticos = [];

        foreach ($rankings as $ranking) {
            $clasificacion = [];
            foreach ($ranking->getPeliculas() as $peli) {
                $valoraciones = $peli->getValoraciones();

                $media = 0;
                if (count($valoraciones) > 0) {
                    $suma = array_reduce($valoraciones->toArray(), fn($acc, $v) => $acc + $v->getPuntuacion(), 0);
                    $media = $suma / count($valoraciones);
                }

                $clasificacion[] = [
                    'pelicula' => $peli,
                    'votos' => count($peli->getRankings()),
                    'media' => $media
                ];
            }

            usort($clasificacion, fn($a, $b) => $b['votos'] <=> $a['votos']);

            $datosEstadisticos[] = [
                'ranking' => $ranking,
                'clasificacion' => $clasificacion
            ];
        }

        return $this->render('admin_pelicula/estadisticas_globales.html.twig', [
            'datos' => $datosEstadisticos
        ]);
    }

    #[Route('/catalogo', name: 'app_pelicula_catalogo', methods: ['GET'])]
    public function catalogo(PeliculaRepository $peliculaRepository, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $busqueda = $request->query->get('q');
        $qb = $peliculaRepository->createQueryBuilder('p');

        if ($busqueda) {
            $qb->andWhere('p.titulo LIKE :query OR p.genre LIKE :query')
                ->setParameter('query', '%'.$busqueda.'%');
        }

        $peliculasRaw = $qb->getQuery()->getResult();
        $peliculasFinal = [];

        foreach ($peliculasRaw as $peli) {
            $vals = $peli->getValoraciones();
            $media = count($vals) > 0
                ? array_reduce($vals->toArray(), fn($s, $v) => $s + $v->getPuntuacion(), 0) / count($vals)
                : 0;

            $peliculasFinal[] = [
                'peli' => $peli,
                'media' => $media
            ];
        }

        usort($peliculasFinal, fn($a, $b) => $b['media'] <=> $a['media']);

        return $this->render('admin_pelicula/index_catalogo.html.twig', [
            'peliculas_con_nota' => $peliculasFinal,
            'texto_busqueda' => $busqueda
        ]);
    }

    #[Route('/peli/{id}', name: 'app_admin_pelicula_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Pelicula $pelicula): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->render('admin_pelicula/show.html.twig', ['pelicula' => $pelicula]);
    }

    #[Route('/peli/{id}/valorar', name: 'app_pelicula_valorar', methods: ['POST'])]
    public function valorar(Request $request, Pelicula $pelicula, EntityManagerInterface $entityManager, ValoracionRepository $vRepo): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $puntuacion = $request->request->get('puntuacion');
        $usuario = $this->getUser();

        if ($puntuacion && $usuario) {
            $valoracion = $vRepo->findOneBy(['pelicula' => $pelicula, 'usuario' => $usuario]) ?? new Valoracion();
            $valoracion->setPuntuacion((int)$puntuacion);
            $valoracion->setComentario($request->request->get('comentario'));
            $valoracion->setPelicula($pelicula);
            $valoracion->setUsuario($usuario);

            $entityManager->persist($valoracion);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_pelicula_show', ['id' => $pelicula->getId()]);
    }

    #[Route('/rankings', name: 'app_ranking_index', methods: ['GET'])]
    public function rankingIndex(RankingRepository $rankingRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->render('admin_pelicula/listado_rankings.html.twig', [
            'rankings' => $rankingRepository->findAll()
        ]);
    }

    #[Route('/ranking/{id}', name: 'app_ranking_view', methods: ['GET'])]
    public function viewRanking(Ranking $ranking): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->render('admin_pelicula/misuperranking.html.twig', [
            'ranking' => $ranking,
            'peliculas' => $ranking->getPeliculas()
        ]);
    }

    #[Route('/ranking/{id}/reorder', name: 'app_ranking_reorder', methods: ['POST'])]
    public function reorder(Ranking $ranking, Request $request, EntityManagerInterface $entityManager, PeliculaRepository $peliculaRepository): Response
    {
        if (!$this->getUser()) return $this->json(['error' => 'No autorizado'], 403);

        $data = json_decode($request->getContent(), true);
        $idsOrdered = $data['ids'] ?? [];

        if (empty($idsOrdered)) return $this->json(['error' => 'Datos vacíos'], 400);

        foreach ($ranking->getPeliculas() as $pelicula) {
            $ranking->removePelicula($pelicula);
        }
        $entityManager->flush();

        foreach ($idsOrdered as $idPeli) {
            $peli = $peliculaRepository->find($idPeli);
            if ($peli) $ranking->addPelicula($peli);
        }

        $entityManager->flush();
        return $this->json(['status' => 'success']);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/dashboard', name: 'app_admin_dashboard', methods: ['GET'])]
    public function dashboard(PeliculaRepository $pRepo, UsuarioRepository $uRepo, ValoracionRepository $vRepo): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'total_peliculas' => $pRepo->count([]),
            'total_usuarios' => $uRepo->count([]),
            'total_valoraciones' => $vRepo->count([]),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/auditoria', name: 'app_admin_auditoria', methods: ['GET'])]
    public function auditoria(PeliculaRepository $pRepo, UsuarioRepository $uRepo, ValoracionRepository $vRepo, RankingRepository $rRepo): Response
    {
        return $this->render('admin/auditoria.html.twig', [
            'total_usuarios' => $uRepo->count([]),
            'total_peliculas' => $pRepo->count([]),
            'total_comentarios' => $vRepo->count([]),
            'total_rankings' => $rRepo->count([]),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/lista', name: 'app_admin_pelicula_index', methods: ['GET'])]
    public function index(PeliculaRepository $peliculaRepository): Response
    {
        return $this->render('admin_pelicula/index.html.twig', [
            'peliculas' => $peliculaRepository->findAll()
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/peli/nueva', name: 'app_admin_pelicula_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $pelicula = new Pelicula();
        $form = $this->createForm(PeliculaType::class, $pelicula);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($pelicula);
            $entityManager->flush();
            return $this->redirectToRoute('app_admin_pelicula_index');
        }

        return $this->render('admin_pelicula/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/peli/{id}/editar', name: 'app_admin_pelicula_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Pelicula $pelicula, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PeliculaType::class, $pelicula);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_admin_pelicula_index');
        }

        return $this->render('admin_pelicula/edit.html.twig', [
            'pelicula' => $pelicula,
            'form' => $form->createView()
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/peli/{id}', name: 'app_admin_pelicula_delete', methods: ['POST'])]
    public function delete(Request $request, Pelicula $pelicula, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$pelicula->getId(), $request->request->get('_token'))) {
            $entityManager->remove($pelicula);
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_admin_pelicula_index');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/ranking/nuevo', name: 'app_admin_ranking_new', methods: ['GET', 'POST'])]
    public function newRanking(Request $request, EntityManagerInterface $entityManager): Response
    {
        $ranking = new Ranking();
        $form = $this->createForm(RankingType::class, $ranking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($ranking);
            $entityManager->flush();
            return $this->redirectToRoute('app_ranking_index');
        }

        return $this->render('admin_pelicula/new_ranking.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/usuarios/exportar', name: 'app_admin_users_export', methods: ['GET'])]
    public function exportarUsuarios(): Response
    {
        $this->addFlash('info', 'Exportación de usuarios iniciada.');
        return $this->redirectToRoute('app_admin_dashboard');
    }

    /**
     * EDITAR RANKING (Sólo Admin)
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/ranking/{id}/editar', name: 'app_admin_ranking_edit', methods: ['GET', 'POST'])]
    public function editRanking(Request $request, Ranking $ranking, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RankingType::class, $ranking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Ranking actualizado correctamente.');
            return $this->redirectToRoute('app_ranking_index');
        }

        return $this->render('admin_pelicula/new_ranking.html.twig', [
            'ranking' => $ranking,
            'form' => $form->createView(),
            'edit_mode' => true
        ]);
    }

    /**
     * BORRAR RANKING (Sólo Admin)
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/ranking/{id}/borrar', name: 'app_admin_ranking_delete', methods: ['POST'])]
    public function deleteRanking(Request $request, Ranking $ranking, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$ranking->getId(), $request->request->get('_token'))) {
            $entityManager->remove($ranking);
            $entityManager->flush();
            $this->addFlash('danger', 'Ranking eliminado.');
        }

        return $this->redirectToRoute('app_ranking_index');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/peli/{id}/eliminar', name: 'app_admin_pelicula_delete_fixed', methods: ['POST'])]
    public function deletePeli(Request $request, Pelicula $pelicula, EntityManagerInterface $entityManager): Response
    {
        // Verificamos el token de seguridad enviado desde el formulario
        if ($this->isCsrfTokenValid('delete'.$pelicula->getId(), $request->request->get('_token'))) {
            $entityManager->remove($pelicula);
            $entityManager->flush();
            $this->addFlash('danger', 'Película eliminada correctamente.');
        }

        return $this->redirectToRoute('app_admin_pelicula_index');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/api/import', name: 'app_admin_api_import', methods: ['GET'])]
    public function importApi(
        HttpClientInterface $httpClient,
        EntityManagerInterface $entityManager,
        PeliculaRepository $pRepo
    ): Response {
        try {
            // 1. Llamada a tu API externa
            $response = $httpClient->request('GET', 'https://devsapihub.com/api-movies');
            $movies = $response->toArray();

            foreach ($movies as $movieData) {
                // Evitar duplicados por título
                $existe = $pRepo->findOneBy(['titulo' => $movieData['title']]);

                if (!$existe) {
                    $pelicula = new Pelicula();
                    $pelicula->setTitulo($movieData['title']);
                    $pelicula->setGenre($movieData['genre'] ?? 'Sin género');
                    $pelicula->setImageUrl($movieData['poster'] ?? 'default.jpg');
                    // Si tu entidad tiene estos campos, actívalos:
                    // $pelicula->setDescripcion($movieData['description']);

                    $entityManager->persist($pelicula);
                }
            }

            $entityManager->flush();
            $this->addFlash('success', '¡Sincronización exitosa! El catálogo se ha actualizado desde DevsApiHub.');

        } catch (\Exception $e) {
            $this->addFlash('danger', 'Error al conectar con la API: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }
}
