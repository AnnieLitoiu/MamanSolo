<?php

namespace App\DataFixtures;

use App\Entity\Utilisateur;
use App\Entity\Profil;
use App\Entity\TableauDeBord;
use App\Entity\Partie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture implements DependentFixtureInterface
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Créer des utilisateurs avec profils et scores variés (noms arabes, roumains et belges)
        $nomsUtilisateurs = [
            ['nom' => 'Sophie Dupont', 'email' => 'sophie.dupont@example.com', 'score' => 7600],
            ['nom' => 'Julie Claessens', 'email' => 'julie.claessens@example.com', 'score' => 8700],
            ['nom' => 'Charlotte Van den Berg', 'email' => 'charlotte.vandenberg@example.com', 'score' => 6800],
            ['nom' => 'Marie Janssens', 'email' => 'marie.janssens@example.com', 'score' => 9100],
            ['nom' => 'Anne Vermeulen', 'email' => 'anne.vermeulen@example.com', 'score' => 7900],
            ['nom' => 'Fatima El Amrani', 'email' => 'fatima.elamrani@example.com', 'score' => 8500],
            ['nom' => 'Amina Benali', 'email' => 'amina.benali@example.com', 'score' => 7800],
            ['nom' => 'Leila Kaddouri', 'email' => 'leila.kaddouri@example.com', 'score' => 9200],
            ['nom' => 'Samira Mansouri', 'email' => 'samira.mansouri@example.com', 'score' => 6900],
            ['nom' => 'Nadia Hamidi', 'email' => 'nadia.hamidi@example.com', 'score' => 8100],
            ['nom' => 'Elena Popescu', 'email' => 'elena.popescu@example.com', 'score' => 7500],
            ['nom' => 'Ioana Ionescu', 'email' => 'ioana.ionescu@example.com', 'score' => 8800],
            ['nom' => 'Andreea Constantinescu', 'email' => 'andreea.constantinescu@example.com', 'score' => 7200],
            ['nom' => 'Maria Dumitrescu', 'email' => 'maria.dumitrescu@example.com', 'score' => 9500],
            ['nom' => 'Gabriela Stanescu', 'email' => 'gabriela.stanescu@example.com', 'score' => 8300],
        ];

        $typesPartie = ['bebe', 'ado', 'deux'];

        foreach ($nomsUtilisateurs as $index => $userData) {
            // Créer l'utilisateur
            $utilisateur = new Utilisateur();
            $utilisateur->setNom($userData['nom']);
            $utilisateur->setEmail($userData['email']);
            $utilisateur->setMotDePasse($this->hasher->hashPassword($utilisateur, 'password123'));
            $manager->persist($utilisateur);

            // Créer un profil pour cet utilisateur
            $profil = new Profil();
            $profil->setUtilisateur($utilisateur);
            $profil->setScore($userData['score']);
            $profil->setField('Type: ' . $typesPartie[$index % 3]);
            $manager->persist($profil);

            // Créer un tableau de bord pour ce profil
            $tableauDeBord = new TableauDeBord();
            $tableauDeBord->setProfil($profil);
            $tableauDeBord->setMeilleurScore($userData['score']);
            $tableauDeBord->setEnregistreScore(true);
            $tableauDeBord->setClassement([
                'rang' => $index + 1,
                'total_joueurs' => count($nomsUtilisateurs),
                'progression' => rand(-5, 10)
            ]);
            $manager->persist($tableauDeBord);

            // Créer 2-3 parties pour chaque utilisateur
            $nombreParties = rand(2, 3);
            for ($i = 0; $i < $nombreParties; $i++) {
                $partie = new Partie();
                $partie->setUtilisateur($utilisateur);
                $partie->setDate(new \DateTimeImmutable('-' . rand(1, 30) . ' days'));
                $partie->setType($typesPartie[array_rand($typesPartie)]);

                // Budget initial variable
                $budgetInitial = rand(1200, 2500);
                $partie->setBudgetInitial((string)$budgetInitial);

                // Budget courant (peut être positif ou négatif)
                $variation = rand(-500, 800);
                $budgetCourant = $budgetInitial + $variation;
                $partie->setBudgetCourant((string)$budgetCourant);

                // Bien-être
                $partie->setBienEtreInitial(rand(60, 80));

                // Bonheur des enfants
                $partie->setBonheurCourant(rand(50, 90));

                // Progression dans le jeu
                $nbSemaines = rand(8, 12);
                $partie->setNbSemaines($nbSemaines);
                $partie->setSemaineCourante(rand(1, $nbSemaines));

                // État aléatoire
                $etats = ['EN_COURS', 'TERMINEE', 'TERMINEE', 'TERMINEE']; // Plus de parties terminées
                $partie->setEtat($etats[array_rand($etats)]);

                $manager->persist($partie);
            }
        }

        // Créer un utilisateur de test avec mot de passe simple
        $testUser = new Utilisateur();
        $testUser->setNom('Test User');
        $testUser->setEmail('test@test.com');
        $testUser->setMotDePasse($this->hasher->hashPassword($testUser, 'test'));
        $manager->persist($testUser);

        $testProfil = new Profil();
        $testProfil->setUtilisateur($testUser);
        $testProfil->setScore(10000);
        $testProfil->setField('Type: bebe');
        $manager->persist($testProfil);

        $testTableau = new TableauDeBord();
        $testTableau->setProfil($testProfil);
        $testTableau->setMeilleurScore(10000);
        $testTableau->setEnregistreScore(true);
        $testTableau->setClassement(['rang' => 1, 'total_joueurs' => 16, 'progression' => 15]);
        $manager->persist($testTableau);

        // Créer une partie en cours pour le test user
        $testPartie = new Partie();
        $testPartie->setUtilisateur($testUser);
        $testPartie->setDate(new \DateTimeImmutable());
        $testPartie->setType('bebe');
        $testPartie->setBudgetInitial('2000');
        $testPartie->setBudgetCourant('1750');
        $testPartie->setBienEtreInitial(75);
        $testPartie->setBonheurCourant(80);
        $testPartie->setSemaineCourante(5);
        $testPartie->setNbSemaines(12);
        $testPartie->setEtat('EN_COURS');
        $manager->persist($testPartie);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            EvenementFixtures::class,
        ];
    }
}
