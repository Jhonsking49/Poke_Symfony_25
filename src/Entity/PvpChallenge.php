<?php

namespace App\Entity;

use App\Repository\PvpChallengeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\Column]
    private ?int $type = null;

    /**
     * @var Collection<int, Pokemons>
     */
    #[ORM\ManyToMany(targetEntity: Pokemons::class, inversedBy: 'pvpChallengerTeam')]
    private Collection $challengerTeam;

    /**
     * @var Collection<int, Pokemons>
     */
    #[ORM\ManyToMany(targetEntity: Pokemons::class, inversedBy: 'pvpEnemyTeam')]
    private Collection $enemyTeam;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->challengerTeam = new ArrayCollection();
        $this->enemyTeam = new ArrayCollection();
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

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection<int, Pokemons>
     */
    public function getChallengerTeam(): Collection
    {
        return $this->challengerTeam;
    }

    public function addChallengerTeam(Pokemons $challengerTeam): static
    {
        if (!$this->challengerTeam->contains($challengerTeam)) {
            $this->challengerTeam->add($challengerTeam);
        }

        return $this;
    }

    public function removeChallengerTeam(Pokemons $challengerTeam): static
    {
        $this->challengerTeam->removeElement($challengerTeam);

        return $this;
    }

    /**
     * @return Collection<int, Pokemons>
     */
    public function getEnemyTeam(): Collection
    {
        return $this->enemyTeam;
    }

    public function addEnemyTeam(Pokemons $enemyTeam): static
    {
        if (!$this->enemyTeam->contains($enemyTeam)) {
            $this->enemyTeam->add($enemyTeam);
        }

        return $this;
    }

    public function removeEnemyTeam(Pokemons $enemyTeam): static
    {
        $this->enemyTeam->removeElement($enemyTeam);

        return $this;
    }
}
