<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
class Utilisateur implements PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $motDePasse = null;

    #[ORM\OneToOne(mappedBy: 'Utilisateur', cascade: ['persist', 'remove'])]
    private ?Situation $situation = null;

    #[ORM\OneToOne(mappedBy: 'utilisateur', cascade: ['persist', 'remove'])]
    private ?Avatar $avatar = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getMotDePasse(): ?string
    {
        return $this->motDePasse;
    }

    public function setMotDePasse(string $motDePasse): static
    {
        $this->motDePasse = $motDePasse;

        return $this;
    }

    public function getSituation(): ?Situation
    {
        return $this->situation;
    }

    public function setSituation(?Situation $situation): static
    {
        // unset the owning side of the relation if necessary
        if ($situation === null && $this->situation !== null) {
            $this->situation->setUtilisateur(null);
        }

        // set the owning side of the relation if necessary
        if ($situation !== null && $situation->getUtilisateur() !== $this) {
            $situation->setUtilisateur($this);
        }

        $this->situation = $situation;

        return $this;
    }

    public function getAvatar(): ?Avatar
    {
        return $this->avatar;
    }

    public function setAvatar(?Avatar $avatar): static
    {
        // unset the owning side of the relation if necessary
        if ($avatar === null && $this->avatar !== null) {
            $this->avatar->setUtilisateur(null);
        }

        // set the owning side of the relation if necessary
        if ($avatar !== null && $avatar->getUtilisateur() !== $this) {
            $avatar->setUtilisateur($this);
        }

        $this->avatar = $avatar;

        return $this;
    }

    public function getPassword(): ?string
    {
        // on retourne le champ qui contient déjà le mot de passe hashé
        return $this->motDePasse;
    }
}
