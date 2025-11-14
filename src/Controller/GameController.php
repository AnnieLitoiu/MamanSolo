<?php

namespace App\Controller;

use App\Entity\Partie;
use App\Entity\Semaine;
use App\Entity\Evenement;
use App\Entity\Option;
use App\Entity\Utilisateur;
use App\Service\GameEngine;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GameController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private GameEngine $engine
    ) {}

    /**
     * Démarre la partie APRÈS le choix familial.
     * Appelée après le formulaire de GameSetupController.
     */
    #[Route('/game/start', name: 'game_start', methods: ['GET'])]
    public function start(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $session      = $request->getSession();
        $composition  = $session->get('compositionFamiliale'); // bebe|ado|deux
        $logement     = $session->get('logement');
        $situationPro = $session->get('situationPro');

        if (!$composition) {
            $this->addFlash('warning', "Choisis d'abord ta situation familiale.");
            return $this->redirectToRoute('game_setup_family');
        }

        /** @var Utilisateur $user */
        $user = $this->getUser();

        // 1) Calculer le budget/bonheur de départ selon la situation
        $presets = [
            'bebe'      => ['budget' => '1200.00', 'bonheur' => 70, 'bienEtre' => 70],
            'ado'       => ['budget' => '1000.00', 'bonheur' => 65, 'bienEtre' => 65],
            'les_deux'  => ['budget' => '900.00',  'bonheur' => 60, 'bienEtre' => 60],
        ];
        $base = $presets[$composition] ?? ['budget' => '1000.00', 'bonheur' => 65, 'bienEtre' => 65];

        // 2) Créer la Partie initiale avec le type de composition
        $partie = (new Partie())
            ->setUtilisateur($user)
            ->setType($composition)
            ->setBudgetInitial($base['budget'])
            ->setBudgetCourant($base['budget'])
            ->setBienEtreInitial($base['bienEtre'])
            ->setBonheurCourant($base['bonheur']);

        // 2bis) Initialiser la Session pour budget/bien-être (sans casser la logique existante)
        // Exemples par défaut; on peut raffiner selon composition/logement/situationPro si besoin
        $session->set('budget', (int) round((float) $base['budget']));
        $session->set('bien_etre_maman', 5);
        $session->set('bien_etre_enfant', 5);

        // 2ter) Initialiser des charges fixes mensuelles selon le logement (valeurs par défaut)
        // On stocke des montants mensuels, appliqués chaque semaine en les divisant par 4
        $chargesFixes = [];
        $logementKey = (string) ($logement ?? 'prive');
        // Valeurs par défaut si aucun setup plus fin n'est fourni
        if ($logementKey === 'social') {
            $chargesFixes['Loyer'] = 450; // mensuel
        } elseif ($logementKey === 'chez_parents') {
            $chargesFixes['Loyer'] = 0;
        } else {
            // locataire privé
            $chargesFixes['Loyer'] = 700;
        }
        // Autres charges (exemples)
        $chargesFixes['Électricité + Internet'] = 90; // mensuel
        $chargesFixes['Transport'] = 50; // mensuel
        $session->set('charges_fixes', $chargesFixes);

        // 3) Démarrer la partie via le GameEngine (4 semaines)
        $this->engine->demarrerPartie($partie, 4);

        // 4) Sauvegarder en base
        $this->em->persist($partie);
        $this->em->flush();

        // 5) Garder l'id de la partie et la composition en session
        $session->set('current_game_id', $partie->getId());
        $session->set('current_composition', $composition);

        // 6) Rediriger vers l'écran de jeu (boucle des semaines)
        return $this->redirectToRoute('game_play', ['id' => $partie->getId()]);
    }

    /**
     * Écran de jeu : affiche la semaine courante + événement + options.
     * GET  = affiche.
     * POST = applique l'option choisie et passe à la semaine suivante.
     */
    #[Route('/game/{id}', name: 'game_play', methods: ['GET', 'POST'])]
    public function play(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var Partie|null $partie */
        $partie = $this->em->getRepository(Partie::class)->find($id);
        if (!$partie) {
            throw $this->createNotFoundException('Partie introuvable.');
        }

        // 🚩 POST : le joueur vient de cliquer sur une option
        if ($request->isMethod('POST') && $partie->getEtat() === 'EN_COURS') {
            $optionId = $request->request->getInt('optionId');

            $semaine = $this->em->getRepository(Semaine::class)
                ->findOneBy([
                    'partie' => $partie,
                    'numero' => $partie->getSemaineCourante(),
                ]);

            if (!$semaine) {
                throw $this->createNotFoundException('Semaine introuvable.');
            }

            /** @var Option|null $option */
            $option    = $this->em->getRepository(Option::class)->find($optionId);
            $evenement = $semaine->getEvenementCourant();

            if (
                !$option ||
                !$evenement ||
                !$evenement->getOptions()->contains($option)
            ) {
                $this->addFlash('error', 'Choix invalide pour cet événement.');
            } else {
                // Session: budget/bien-être
                $session = $request->getSession();
                $budget = (int) $session->get('budget', 300);
                $bienMaman = (int) $session->get('bien_etre_maman', 5);
                $bienEnfant = (int) $session->get('bien_etre_enfant', 5);

                // Appliquer les effets de l'option côté Session
                $budget = max(0, $budget - (int) $option->getCout());
                $bienMaman = max(0, min(10, $bienMaman + (int) $option->getImpactBienEtreMaman()));
                $bienEnfant = max(0, min(10, $bienEnfant + (int) $option->getImpactBienEtreEnfant()));

                // Sauvegarder en Session
                $session->set('budget', $budget);
                $session->set('bien_etre_maman', $bienMaman);
                $session->set('bien_etre_enfant', $bienEnfant);

                // Logique jeu : appliquer les effets + passer à la semaine suivante
                $this->engine->appliquerOption($partie, $semaine, $option);
                $this->engine->cloturerSemaine($partie, $semaine);
                $this->em->flush();

                // Partie terminée ? → résumé
                if ($partie->getEtat() === 'TERMINE') {
                    return $this->redirectToRoute('game_summary', ['id' => $partie->getId()]);
                }

                // Sinon on recharge la même route pour la semaine suivante
                return $this->redirectToRoute('game_play', ['id' => $partie->getId()]);
            }
        }

        // 🚩 GET : afficher la semaine courante
        $semaine = $this->em->getRepository(Semaine::class)
            ->findOneBy([
                'partie' => $partie,
                'numero' => $partie->getSemaineCourante(),
            ]);

        if (!$semaine) {
            throw $this->createNotFoundException('Semaine introuvable.');
        }

        $evenement = null;

        if ($partie->getEtat() === 'EN_COURS') {
            // Appliquer les charges fixes une fois par semaine (au premier affichage GET)
            $session = $request->getSession();
            $appliedKey = sprintf('charges_appliquees_%d_%d', $partie->getId(), $partie->getSemaineCourante());
            $chargesFixesAppliquees = [];
            if (!$session->get($appliedKey, false)) {
                $chargesFixes = (array) $session->get('charges_fixes', []);
                $totalHebdo = 0;
                foreach ($chargesFixes as $lib => $mensuel) {
                    $hebdo = (int) round(((int) $mensuel) / 4);
                    $chargesFixesAppliquees[$lib] = $hebdo;
                    $totalHebdo += $hebdo;
                }
                if ($totalHebdo > 0) {
                    $budget = (int) $session->get('budget', 300);
                    $budget = max(0, $budget - $totalHebdo);
                    $session->set('budget', $budget);
                    $session->set($appliedKey, true);
                    $session->set('charges_fixes_appliquees', $chargesFixesAppliquees);
                }
            } else {
                $chargesFixesAppliquees = (array) $session->get('charges_fixes_appliquees', []);
            }

            $evenement = $semaine->getEvenementCourant();

            // Si aucun événement encore assigné à cette semaine, on en pioche un aléatoire
            if (!$evenement) {
                // Récupérer la composition familiale (bebe, ado, deux)
                $composition = $partie->getType() ?? 'bebe';

                // Mapper "les_deux" vers "deux" pour correspondre au JSON
                if ($composition === 'les_deux') {
                    $composition = 'deux';
                }

                $repoEvt = $this->em->getRepository(Evenement::class);

                // Chercher tous les événements correspondant à cette composition
                $candidats = $repoEvt->findBy(['scenario' => $composition]);

                if ($candidats) {
                    // Sélectionner un événement aléatoire
                    $evenement = $candidats[array_rand($candidats)];
                    $semaine->setEvenementCourant($evenement);
                    $this->em->flush();
                }
            }
        }

        // Lire la session pour l'affichage
        $session = $request->getSession();
        $sessionBudget = (int) $session->get('budget', 300);
        $sessionBienMaman = (int) $session->get('bien_etre_maman', 5);
        $sessionBienEnfant = (int) $session->get('bien_etre_enfant', 5);
        $chargesFixesAppliquees = (array) $session->get('charges_fixes_appliquees', []);

        return $this->render('game/play.html.twig', [
            'partie'    => $partie,
            'semaine'   => $semaine,
            'evenement' => $evenement,
            'session_budget' => $sessionBudget,
            'session_bien_etre_maman' => $sessionBienMaman,
            'session_bien_etre_enfant' => $sessionBienEnfant,
            'charges_fixes_appliquees' => $chargesFixesAppliquees,
        ]);
    }

    /**
     * Résumé final de la partie.
     */
    #[Route('/game/{id}/resume', name: 'game_summary', methods: ['GET'])]
    public function summary(int $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var Partie|null $partie */
        $partie = $this->em->getRepository(Partie::class)->find($id);
        if (!$partie) {
            throw $this->createNotFoundException('Partie introuvable.');
        }

        $resume = $this->engine->resumeFinal($partie);

        // Enregistrer le score dans le profil de l'utilisateur
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $profilRepo = $this->em->getRepository(\App\Entity\Profil::class);
        $profil = $profilRepo->findOneBy(['utilisateur' => $user]);

        if ($profil) {
            // Mettre à jour le score si c'est un meilleur score
            $scoreActuel = $profil->getScore() ?? 0;
            if ($resume['score'] > $scoreActuel) {
                $profil->setScore($resume['score']);
                $this->em->flush();
            }
        } else {
            // Créer un nouveau profil si nécessaire
            $profil = new \App\Entity\Profil();
            $profil->setUtilisateur($user);
            $profil->setScore($resume['score']);
            $this->em->persist($profil);
            $this->em->flush();
        }

        return $this->render('game/summary.html.twig', [
            'partie' => $partie,
            'resume' => $resume,
        ]);
    }
}

