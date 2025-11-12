<?php

namespace App\Controller;

use App\Form\FamilyChoiceType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/setup')]
class GameSetupController extends AbstractController
{
    // Page où l'on choisit la composition familiale (Bébé / Ado / Les deux)
    #[Route('/family', name: 'game_setup_family')]
    public function family(Request $request): Response
    {
        // On impose que l'utilisateur soit connecté avant de démarrer une partie
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // On construit le formulaire (simple radio boutons)
        $form = $this->createForm(FamilyChoiceType::class);
        $form->handleRequest($request);

        // Si l'utilisateur a validé le formulaire sans erreur
        if ($form->isSubmitted() && $form->isValid()) {
            // On récupère la valeur choisie (bebe / ado / les_deux)
            $composition = $form->get('composition')->getData();

            // On la stocke en session (rapide, pas de migration pour l’instant)
            $request->getSession()->set('compositionFamiliale', $composition);

            // Ensuite on démarre réellement la partie ,route game_new
            return $this->redirectToRoute('game_new');
        }

        // Premier affichage ou formulaire invalide ,on ré-affiche le form
        return $this->render('game/setup_family.html.twig', [
            'form' => $form,
        ]);
    }
}
