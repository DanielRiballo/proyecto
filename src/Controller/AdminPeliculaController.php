<?php

namespace App\Controller;

use App\Entity\Pelicula;
use App\Entity\Valoracion;
use App\Form\PeliculaType;
use App\Repository\PeliculaRepository;
use App\Repository\UsuarioRepository;
use App\Repository\ValoracionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AdminPeliculaController extends AbstractController
{
    /**
     * PÁGINA DE INICIO PÚBLICA
     */
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('home/index.html.twig');
    }

    /**
     * PANEL DE ADMINISTRACIÓN (La imagen azul con tarjetas)
     */
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

    /**
     * GESTIÓN DE CATÁLOGO (Listado en formato TABLA)
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/lista', name: 'app_admin_pelicula_index', methods: ['GET'])]
    public function index(PeliculaRepository $peliculaRepository): Response
    {
        // Esta es la ruta que quieres que cargue la lista "de antes"
        return $this->render('admin_pelicula/index.html.twig', [
            'peliculas' => $peliculaRepository->findAll(),
        ]);
    }

    /**
     * CATÁLOGO VISUAL (El que tiene las imágenes/posters)
     */
    #[Route('/catalogo', name: 'app_pelicula_catalogo', methods: ['GET'])]
    public function catalogo(PeliculaRepository $peliculaRepository): Response
    {
        if (!$this->getUser()) return $this->redirectToRoute('app_login');

        return $this->render('admin_pelicula/show.html.twig', [
            'peliculas' => $peliculaRepository->findAll(),
            'pelicula' => null,
        ]);
    }

    /**
     * RANKING DE PELÍCULAS
     */
    #[Route('/ranking/ver/{genero}', name: 'app_admin_ranking_genero', defaults: ['genero' => null], methods: ['GET'])]
    public function ranking(PeliculaRepository $peliculaRepository, Request $request, ?string $genero): Response
    {
        if (!$this->getUser()) return $this->redirectToRoute('app_home');

        $busqueda = $request->query->get('q');
        $generosDisponibles = ['Drama', 'Adventure', 'Crime', 'Action', 'Comedy', 'Fantasy', 'Western', 'Sci-Fi', 'Biography'];

        $qb = $peliculaRepository->createQueryBuilder('p')
            ->leftJoin('p.valoraciones', 'v')
            ->select('p', 'COALESCE(AVG(v.puntuacion), 0) as HIDDEN media_orden')
            ->groupBy('p.id')
            ->orderBy('media_orden', 'DESC')
            ->addOrderBy('p.titulo', 'ASC');

        if ($genero && $genero !== 'all') {
            $qb->andWhere('p.genre = :g')->setParameter('g', $genero);
        }

        if ($busqueda) {
            $qb->andWhere('p.titulo LIKE :query OR p.genre LIKE :query')
                ->setParameter('query', '%'.$busqueda.'%');
        }

        return $this->render('admin_pelicula/ranking.html.twig', [
            'peliculas' => $qb->getQuery()->getResult(),
            'categoria_actual' => $genero,
            'texto_busqueda' => $busqueda,
            'generos' => $generosDisponibles
        ]);
    }

    /**
     * FICHA DE PELÍCULA
     */
    #[Route('/peli/{id}', name: 'app_admin_pelicula_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Pelicula $pelicula): Response
    {
        if (!$this->getUser()) return $this->redirectToRoute('app_home');

        return $this->render('admin_pelicula/show.html.twig', [
            'pelicula' => $pelicula,
            'peliculas' => null
        ]);
    }

    /**
     * ACCIÓN DE VALORAR
     */
    #[Route('/peli/{id}/valorar', name: 'app_pelicula_valorar', methods: ['POST'])]
    public function valorar(Request $request, Pelicula $pelicula, EntityManagerInterface $entityManager, ValoracionRepository $vRepo): Response
    {
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
            $this->addFlash('success', '¡Valoración enviada!');
        }

        return $this->redirectToRoute('app_admin_pelicula_show', ['id' => $pelicula->getId()]);
    }

    /**
     * CREAR NUEVA PELÍCULA
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/nueva', name: 'app_admin_pelicula_new', methods: ['GET', 'POST'])]
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
            'pelicula' => $pelicula,
            'form' => $form,
        ]);
    }

    /**
     * EDITAR PELÍCULA
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/{id}/editar', name: 'app_admin_pelicula_edit', methods: ['GET', 'POST'])]
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
            'form' => $form,
        ]);
    }

    /**
     * ELIMINAR PELÍCULA
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/{id}/borrar', name: 'app_admin_pelicula_delete', methods: ['POST'])]
    public function delete(Request $request, Pelicula $pelicula, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$pelicula->getId(), $request->request->get('_token'))) {
            $entityManager->remove($pelicula);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_pelicula_index');
    }

    /**
     * BOTONES ADICIONALES DEL DASHBOARD (API Y AUDITORÍA)
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/importar-api', name: 'app_admin_api_import', methods: ['GET'])]
    public function importFromApi(): Response
    {
        $this->addFlash('info', 'Sincronización finalizada.');
        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/auditoria', name: 'app_admin_auditoria', methods: ['GET'])]
    public function auditoria(): Response
    {
        $this->addFlash('info', 'Accediendo al registro de auditoría...');
        return $this->redirectToRoute('app_admin_dashboard');
    }
}
