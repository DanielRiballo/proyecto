<?php

namespace App\Command;

use App\Entity\Pelicula;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:importar-pelicula',
    description: 'Descarga películas de la API y las guarda en la base de datos',
)]
class ImportarPeliculasCommand extends Command
{
    private $client;
    private $entityManager;

    // Aquí pondremos la URL de tu API (la que te da ese JSON)
    // Si no tienes la URL pública, avísame y cambiamos esto para leer un archivo local.
    private const API_URL = 'https://devsapihub.com/api-movies';

    public function __construct(HttpClientInterface $client, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->client = $client;
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Iniciando importación de películas...');

        // 1. Descargar el JSON
        // Si el JSON lo tienes en un archivo y no en una URL, avísame para cambiar esta línea
        try {
            $response = $this->client->request('GET', self::API_URL);
            $peliculasArray = $response->toArray();
        } catch (\Exception $e) {
            $io->error('Error al descargar la API: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // 2. Guardar en Base de Datos
        foreach ($peliculasArray as $datos) {
            // Buscamos si ya existe para no duplicarla
            $existe = $this->entityManager->getRepository(Pelicula::class)->findOneBy(['apiId' => $datos['id']]);

            if ($existe) {
                $io->text("La película ID " . $datos['id'] . " ya existe. Saltando...");
                continue;
            }

            $pelicula = new Pelicula();

            // --- AQUÍ ESTÁ LA CORRECCIÓN DE NOMBRES ---
            $pelicula->setApiId($datos['id']);
            $pelicula->setTitulo($datos['title']);          // Antes tenías setTitle
            $pelicula->setDescription($datos['description']); // Este lo corregimos antes
            $pelicula->setyear($datos['year']);             // En vez de setYear
            $pelicula->setImageUrl($datos['image_url']);      // En vez de setImageUrl
            $pelicula->setGenre($datos['genre']);          // En vez de setGenre
            $pelicula->setStars($datos['stars']);  // En vez de setStars
            // ------------------------------------------

            $this->entityManager->persist($pelicula);
            $io->text("Importada: " . $datos['title']);
        }

        $this->entityManager->flush();

        $io->success('¡Importación completada con éxito!');

        return Command::SUCCESS;
    }
}
