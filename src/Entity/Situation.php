<?php

namespace App\Entity;

use App\Repository\SituationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SituationRepository::class)]
class Situation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $nbEnfants = null;

    #[ORM\Column]
    private ?int $revenuMensuel = null;

    #[ORM\Column(length: 255)]
    private ?string $logement = null;

    #[ORM\OneToOne(inversedBy: 'situation', cascade: ['persist', 'remove'])]
    private ?Utilisateur $Utilisateur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNbEnfants(): ?int
    {
        return $this->nbEnfants;
    }

    public function setNbEnfants(int $nbEnfants): static
    {
        $this->nbEnfants = $nbEnfants;

        return $this;
    }

    public function getRevenuMensuel(): ?int
    {
        return $this->revenuMensuel;
    }

    public function setRevenuMensuel(int $revenuMensuel): static
    {
        $this->revenuMensuel = $revenuMensuel;

        return $this;
    }

    public function getLogement(): ?string
    {
        return $this->logement;
    }

    public function setLogement(string $logement): static
    {
        $this->logement = $logement;

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->Utilisateur;
    }

    public function setUtilisateur(?Utilisateur $Utilisateur): static
    {
        $this->Utilisateur = $Utilisateur;

        return $this;
    }
}
