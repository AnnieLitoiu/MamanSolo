<?php
namespace App\Controller;


use App\Service\GameDesignStats;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;


#[Route('/api')]
class ScoresController extends AbstractController
{
    // Page "Tableau des scores"
    #[Route('/scores', name: 'scores_index', methods: ['GET'])]
    public function scores(GameDesignStats $stats): Response
    {
        $scores = $stats->calculerScoresMoyens();
        // Pour l’instant on affiche une page vide/placeholder.
        // On pourra y injecter les vrais scores plus tard.
        return $this->render('scores/index.html.twig', [
            'scores' => $scores,
        ]);
    }

}

