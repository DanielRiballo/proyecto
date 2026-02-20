<?php

namespace App\Entity;

use App\Repository\RankingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RankingRepository::class)]
class Ranking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    #[ORM\ManyToOne(inversedBy: 'rankings')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Usuario $usuario = null;

    #[ORM\ManyToMany(targetEntity: Pelicula::class, inversedBy: 'rankings')]
    #[ORM\JoinTable(name: 'ranking_pelicula')]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $peliculas;

    public function __construct()
    {
        $this->peliculas = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;
        return $this;
    }

    public function getUsuario(): ?Usuario
    {
        return $this->usuario;
    }

    public function setUsuario(?Usuario $usuario): static
    {
        $this->usuario = $usuario;
        return $this;
    }

    public function getPeliculas(): Collection
    {
        return $this->peliculas;
    }

    public function addPelicula(Pelicula $pelicula): static
    {
        if (!$this->peliculas->contains($pelicula)) {
            $this->peliculas->add($pelicula);
        }
        return $this;
    }

    public function removePelicula(Pelicula $pelicula): static
    {
        $this->peliculas->removeElement($pelicula);
        return $this;
    }

    public function __toString(): string
    {
        return $this->nombre ?? 'Nuevo Ranking';
    }

    public function setPeliculas(Collection $peliculas): static
    {
        $this->peliculas = $peliculas;
        return $this;
    }
}
