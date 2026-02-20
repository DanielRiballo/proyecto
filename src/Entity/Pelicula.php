<?php

namespace App\Entity;

use App\Repository\PeliculaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PeliculaRepository::class)]
class Pelicula
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $apiId = null;

    #[ORM\Column(length: 255)]
    private ?string $titulo = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?int $year = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $image_url = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $genre = null;

    #[ORM\Column(nullable: true)]
    private ?float $stars = null;

    #[ORM\OneToMany(targetEntity: Valoracion::class, mappedBy: 'pelicula', orphanRemoval: true)]
    private Collection $valoraciones;

    #[ORM\ManyToMany(targetEntity: Ranking::class, mappedBy: 'peliculas')]
    private Collection $rankings;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    public function __construct()
    {
        $this->valoraciones = new ArrayCollection();
        $this->rankings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApiId(): ?int
    {
        return $this->apiId;
    }

    public function setApiId(int $apiId): static
    {
        $this->apiId = $apiId;
        return $this;
    }

    public function getTitulo(): ?string
    {
        return $this->titulo;
    }

    public function setTitulo(string $titulo): static
    {
        $this->titulo = $titulo;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): static
    {
        $this->year = $year;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->image_url;
    }

    public function setImageUrl(?string $image_url): static
    {
        $this->image_url = $image_url;
        return $this;
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(?string $genre): static
    {
        $this->genre = $genre;
        return $this;
    }

    public function getStars(): ?float
    {
        return $this->stars;
    }

    public function setStars(?float $stars): static
    {
        $this->stars = $stars;
        return $this;
    }

    public function getValoraciones(): Collection
    {
        return $this->valoraciones;
    }

    public function addValoracion(Valoracion $valoracion): static
    {
        if (!$this->valoraciones->contains($valoracion)) {
            $this->valoraciones->add($valoracion);
            $valoracion->setPelicula($this);
        }
        return $this;
    }

    public function removeValoracion(Valoracion $valoracion): static
    {
        if ($this->valoraciones->removeElement($valoracion)) {
            if ($valoracion->getPelicula() === $this) {
                $valoracion->setPelicula(null);
            }
        }
        return $this;
    }

    public function getRankings(): Collection
    {
        return $this->rankings;
    }

    public function addRanking(Ranking $ranking): static
    {
        if (!$this->rankings->contains($ranking)) {
            $this->rankings->add($ranking);
            $ranking->addPelicula($this);
        }
        return $this;
    }

    public function removeRanking(Ranking $ranking): static
    {
        if ($this->rankings->removeElement($ranking)) {
            $ranking->removePelicula($this);
        }
        return $this;
    }

    public function getMediaReal(): float
    {
        if ($this->valoraciones->isEmpty()) {
            return 0.0;
        }

        $suma = 0;
        foreach ($this->valoraciones as $valoracion) {
            $suma += $valoracion->getEstrellas() ?? 0;
        }

        return round($suma / count($this->valoraciones), 1);
    }
}
