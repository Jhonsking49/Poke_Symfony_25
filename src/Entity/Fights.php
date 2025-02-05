<?php

namespace App\Entity;

use App\Repository\FightsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FightsRepository::class)]
class Fights
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'fightspokeuser')]
    private ?Pokemons $pokeuser = null;

    #[ORM\ManyToOne(inversedBy: 'fightspokenemy')]
    private ?Pokemons $pokenemy = null;

    #[ORM\Column]
    private ?int $result = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPokeuser(): ?Pokemons
    {
        return $this->pokeuser;
    }

    public function setPokeuser(?Pokemons $pokeuser): static
    {
        $this->pokeuser = $pokeuser;

        return $this;
    }

    public function getPokenemy(): ?Pokemons
    {
        return $this->pokenemy;
    }

    public function setPokenemy(?Pokemons $pokenemy): static
    {
        $this->pokenemy = $pokenemy;

        return $this;
    }

    public function getResult(): ?int
    {
        return $this->result;
    }

    public function setResult(int $result): static
    {
        $this->result = $result;

        return $this;
    }
}
