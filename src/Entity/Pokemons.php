<?php

namespace App\Entity;

use App\Repository\PokemonsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PokemonsRepository::class)]
class Pokemons
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'pokemons')]
    private ?Pokeplantilla $pokeplantilla = null;

    #[ORM\Column]
    private ?int $level = null;

    #[ORM\Column]
    private ?int $strength = null;

    #[ORM\ManyToOne(inversedBy: 'pokemons')]
    private ?User $user = null;

    /**
     * @var Collection<int, Fights>
     */
    #[ORM\OneToMany(targetEntity: Fights::class, mappedBy: 'pokeuser')]
    private Collection $fightspokeuser;

    /**
     * @var Collection<int, Fights>
     */
    #[ORM\OneToMany(targetEntity: Fights::class, mappedBy: 'pokenemy')]
    private Collection $fightspokenemy;

    #[ORM\Column]
    private ?int $state = null;

    /**
     * @var Collection<int, PvpChallenge>
     */
    #[ORM\ManyToMany(targetEntity: PvpChallenge::class, mappedBy: 'challengerTeam')]
    private Collection $pvpChallengerTeam;

    /**
     * @var Collection<int, PvpChallenge>
     */
    #[ORM\ManyToMany(targetEntity: PvpChallenge::class, mappedBy: 'enemyTeam')]
    private Collection $pvpEnemyTeam;

    public function __construct()
    {
        $this->fightspokeuser = new ArrayCollection();
        $this->fightspokenemy = new ArrayCollection();
        $this->pvpChallengerTeam = new ArrayCollection();
        $this->pvpEnemyTeam = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPokeplantilla(): ?Pokeplantilla
    {
        return $this->pokeplantilla;
    }

    public function setPokeplantilla(?Pokeplantilla $pokeplantilla): static
    {
        $this->pokeplantilla = $pokeplantilla;

        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(int $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getStrength(): ?int
    {
        return $this->strength;
    }

    public function setStrength(int $strength): static
    {
        $this->strength = $strength;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Fights>
     */
    public function getFightspokeuser(): Collection
    {
        return $this->fightspokeuser;
    }

    public function addFightspokeuser(Fights $fightspokeuser): static
    {
        if (!$this->fightspokeuser->contains($fightspokeuser)) {
            $this->fightspokeuser->add($fightspokeuser);
            $fightspokeuser->setPokeuser($this);
        }

        return $this;
    }

    public function removeFightspokeuser(Fights $fightspokeuser): static
    {
        if ($this->fightspokeuser->removeElement($fightspokeuser)) {
            // set the owning side to null (unless already changed)
            if ($fightspokeuser->getPokeuser() === $this) {
                $fightspokeuser->setPokeuser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Fights>
     */
    public function getFightspokenemy(): Collection
    {
        return $this->fightspokenemy;
    }

    public function addFightspokenemy(Fights $fightspokenemy): static
    {
        if (!$this->fightspokenemy->contains($fightspokenemy)) {
            $this->fightspokenemy->add($fightspokenemy);
            $fightspokenemy->setPokenemy($this);
        }

        return $this;
    }

    public function removeFightspokenemy(Fights $fightspokenemy): static
    {
        if ($this->fightspokenemy->removeElement($fightspokenemy)) {
            // set the owning side to null (unless already changed)
            if ($fightspokenemy->getPokenemy() === $this) {
                $fightspokenemy->setPokenemy(null);
            }
        }

        return $this;
    }

    public function getState(): ?int
    {
        return $this->state;
    }

    public function setState(int $state): static
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return Collection<int, PvpChallenge>
     */
    public function getPvpChallengerTeam(): Collection
    {
        return $this->pvpChallengerTeam;
    }

    public function addPvpChallengerTeam(PvpChallenge $pvpChallengerTeam): static
    {
        if (!$this->pvpChallengerTeam->contains($pvpChallengerTeam)) {
            $this->pvpChallengerTeam->add($pvpChallengerTeam);
            $pvpChallengerTeam->addChallengerTeam($this);
        }

        return $this;
    }

    public function removePvpChallengerTeam(PvpChallenge $pvpChallengerTeam): static
    {
        if ($this->pvpChallengerTeam->removeElement($pvpChallengerTeam)) {
            $pvpChallengerTeam->removeChallengerTeam($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, PvpChallenge>
     */
    public function getPvpEnemyTeam(): Collection
    {
        return $this->pvpEnemyTeam;
    }

    public function addPvpEnemyTeam(PvpChallenge $pvpEnemyTeam): static
    {
        if (!$this->pvpEnemyTeam->contains($pvpEnemyTeam)) {
            $this->pvpEnemyTeam->add($pvpEnemyTeam);
            $pvpEnemyTeam->addEnemyTeam($this);
        }

        return $this;
    }

    public function removePvpEnemyTeam(PvpChallenge $pvpEnemyTeam): static
    {
        if ($this->pvpEnemyTeam->removeElement($pvpEnemyTeam)) {
            $pvpEnemyTeam->removeEnemyTeam($this);
        }

        return $this;
    }
}
