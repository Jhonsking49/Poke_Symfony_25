<?php

namespace App\Entity;

use App\Repository\PvpChallengeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PvpChallengeRepository::class)]
class PvpChallenge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $challenger = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $targetTrainer = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Pokemons $challengerPokemon = null;

    #[ORM\Column(length: 20)]
    private ?string $status = 'pending';

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChallenger(): ?User
    {
        return $this->challenger;
    }

    public function setChallenger(?User $challenger): static
    {
        $this->challenger = $challenger;
        return $this;
    }

    public function getTargetTrainer(): ?User
    {
        return $this->targetTrainer;
    }

    public function setTargetTrainer(?User $targetTrainer): static
    {
        $this->targetTrainer = $targetTrainer;
        return $this;
    }

    public function getChallengerPokemon(): ?Pokemons
    {
        return $this->challengerPokemon;
    }

    public function setChallengerPokemon(?Pokemons $challengerPokemon): static
    {
        $this->challengerPokemon = $challengerPokemon;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
