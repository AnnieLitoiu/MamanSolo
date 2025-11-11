<?php

namespace App\Entity;

use App\Repository\AvatarRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvatarRepository::class)]
class Avatar
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $couleurPeau = null;

    #[ORM\Column(length: 255)]
    private ?string $couleurCheveux = null;

    #[ORM\Column(length: 255)]
    private ?string $styleVestimentaire = null;

    #[ORM\OneToOne(inversedBy: 'avatar', cascade: ['persist', 'remove'])]
    private ?Utilisateur $utilisateur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCouleurPeau(): ?string
    {
        return $this->couleurPeau;
    }

    public function setCouleurPeau(string $couleurPeau): static
    {
        $this->couleurPeau = $couleurPeau;

        return $this;
    }

    public function getCouleurCheveux(): ?string
    {
        return $this->couleurCheveux;
    }

    public function setCouleurCheveux(string $couleurCheveux): static
    {
        $this->couleurCheveux = $couleurCheveux;

        return $this;
    }

    public function getStyleVestimentaire(): ?string
    {
        return $this->styleVestimentaire;
    }

    public function setStyleVestimentaire(string $styleVestimentaire): static
    {
        $this->styleVestimentaire = $styleVestimentaire;

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }
}
