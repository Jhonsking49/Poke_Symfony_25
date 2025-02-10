<?php

namespace App\Entity;

use App\Repository\PokeplantillaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PokeplantillaRepository::class)]
class Pokeplantilla
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    /**
     * @var Collection<int, Pokemons>
     */
    #[ORM\OneToMany(targetEntity: Pokemons::class, mappedBy: 'pokeplantilla')]
    private Collection $pokemons;

    #[ORM\Column(nullable: true)]
    private ?int $evolution = null;

    #[ORM\Column(nullable: true)]
    private ?int $evolevel = null;

    #[ORM\Column(length: 255)]
    private ?string $img = null;

    public function __construct()
    {
        $this->pokemons = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection<int, Pokemons>
     */
    public function getPokemons(): Collection
    {
        return $this->pokemons;
    }

    public function addPokemon(Pokemons $pokemon): static
    {
        if (!$this->pokemons->contains($pokemon)) {
            $this->pokemons->add($pokemon);
            $pokemon->setPokeplantilla($this);
        }

        return $this;
    }

    public function removePokemon(Pokemons $pokemon): static
    {
        if ($this->pokemons->removeElement($pokemon)) {
            // set the owning side to null (unless already changed)
            if ($pokemon->getPokeplantilla() === $this) {
                $pokemon->setPokeplantilla(null);
            }
        }

        return $this;
    }

    public function getEvolution(): ?int
    {
        return $this->evolution;
    }

    public function setEvolution(?int $evolution): static
    {
        $this->evolution = $evolution;

        return $this;
    }

    public function getEvolevel(): ?int
    {
        return $this->evolevel;
    }

    public function setEvolevel(?int $evolevel): static
    {
        $this->evolevel = $evolevel;

        return $this;
    }

    public function getImg(): ?string
    {
        return $this->img;
    }

    public function setImg(string $img): static
    {
        $this->img = $img;

        return $this;
    }
}
