<?php

namespace App\Service;

use App\Entity\Partie;
use App\Entity\Semaine;
use App\Entity\Option;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Mon moteur de jeu.
 * - Je centralise ici la logique: démarrer une partie, appliquer un choix, passer à la semaine suivante, calculer le final.
 * - Comme ça, mes contrôleurs restent très “fins” et mon front (ou API plus tard) n’a qu’à appeler ces méthodes.
 */
class GameEngine
{
    public function __construct(private EntityManagerInterface $em)
    {
        // L'EntityManager me permet de charger/ajouter/modifier des entités facilement.
    }

    /**
     * Démarre une partie: je mets les valeurs par défaut et je crée la semaine 1 si besoin.
     * Je peux passer nbSemaines (par défaut 4).
     */
    public function demarrerPartie(Partie $partie, int $nbSemaines = 4): Partie
    {
        $partie->setEtat('EN_COURS');
        $partie->setSemaineCourante(1);
        $partie->setNbSemaines($nbSemaines);
        $partie->setDate(new \DateTimeImmutable());

        // Si aucune semaine n’existe encore, je crée la semaine 1
        if ($partie->getSemaines()->isEmpty()) {
            $s1 = (new Semaine())
                ->setPartie($partie)
                ->setNumero(1)
                ->setBudgetRestant($partie->getBudgetCourant()) // je pars du budget courant
                ->setBienEtre(0)
                ->setBonheurEnfants(0);

            $this->em->persist($s1);
        }

        return $partie;
    }

    /**
     * Applique les effets d’une option (choix) à la partie + met à jour les valeurs de la semaine.
     * Je garde l’arithmétique simple (float + format 2 décimales) pour éviter d’exiger l’extension bcmath.
     */
    public function appliquerOption(Partie $partie, Semaine $semaine, Option $option): void
    {
        // --- Budget: dans la base c’est un DECIMAL => Doctrine le donne en string.
        $budgetActuel = (float) $partie->getBudgetCourant();
        $deltaBudget  = (float) $option->getDeltaBudget();
        $nouveauBudget = number_format($budgetActuel + $deltaBudget, 2, '.', ''); // je garde 2 décimales propres

        $partie->setBudgetCourant($nouveauBudget);

        // --- Bonheur côté partie (ton modèle stocke bonheurCourant dans Partie)
        $partie->setBonheurCourant(
            $partie->getBonheurCourant() + $option->getDeltaBonheur()
        );

        // --- Indicateurs de la semaine en cours (bilan)
        $semaine->setBudgetRestant($nouveauBudget);
        $semaine->setBienEtre($semaine->getBienEtre() + $option->getDeltaBienEtre());
        $semaine->setBonheurEnfants($semaine->getBonheurEnfants() + $option->getDeltaBonheur());
    }

    /**
     * Clôture la semaine actuelle et prépare la suivante.
     * Si on dépasse le nombre de semaines prévu, j’achève la partie.
     */
    public function cloturerSemaine(Partie $partie, Semaine $semaine): void
    {
        $prochaine = $semaine->getNumero() + 1;

        // Si on a fini le mois (ex: 4 semaines), je termine la partie
        if ($prochaine > $partie->getNbSemaines()) {
            $partie->setEtat('TERMINE');
            return;
        }

        // Sinon, je passe à la semaine suivante
        $partie->setSemaineCourante($prochaine);

        // Je crée la semaine suivante si elle n’existe pas encore
        $existante = $this->em->getRepository(Semaine::class)
            ->findOneBy(['partie' => $partie, 'numero' => $prochaine]);

        if (!$existante) {
            $nouvelle = (new Semaine())
                ->setPartie($partie)
                ->setNumero($prochaine)
                ->setBudgetRestant($partie->getBudgetCourant())
                ->setBienEtre(0)
                ->setBonheurEnfants(0);

            $this->em->persist($nouvelle);
        }
    }

    /**
     * Calcule un résumé final (moyennes + valeurs finales) à afficher à la fin du jeu.
     * Ton front pourra décider du message (“Très bon équilibre”, …) en fonction de ces chiffres.
     */
    public function resumeFinal(Partie $partie): array
    {
        $semaines = $this->em->getRepository(Semaine::class)
            ->findBy(['partie' => $partie], ['numero' => 'ASC']);

        $n = max(count($semaines), 1);
        $sumBien  = 0;
        $sumBonh  = 0;
        $sumBudg  = 0.0;

        foreach ($semaines as $w) {
            $sumBien += $w->getBienEtre();
            $sumBonh += $w->getBonheurEnfants();
            $sumBudg += (float) $w->getBudgetRestant();
        }

        $moyBien = (int) round($sumBien / $n);
        $moyBonh = (int) round($sumBonh / $n);
        $moyBudg = (float) number_format($sumBudg / $n, 2, '.', '');

        return [
            'moyennes' => [
                'bien_etre' => $moyBien,
                'bonheur'   => $moyBonh,
                'budget'    => $moyBudg,
            ],
            'final' => [
                'budget'    => (float) $partie->getBudgetCourant(),
                'bien_etre' => array_sum(array_map(fn($w)=>$w->getBienEtre(), $semaines)),
                'bonheur'   => $partie->getBonheurCourant(),
            ],
        ];
    }
}
