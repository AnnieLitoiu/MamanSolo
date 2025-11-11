<?php

namespace App\DataFixtures;

use App\Entity\Utilisateur;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;

class UtilisateurFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($i = 1; $i <= 10; $i++) {
            $utilisateur = new Utilisateur();
            $utilisateur->setNom("maman" . $i);
            $utilisateur->setEmail("maman" . $i . "@gmail.com");
            $utilisateur->setMotDePasse($this->hasher->hashPassword($utilisateur, "password" . $i));

            $this->addReference("utilisateur" . $i, $utilisateur);

            $manager->persist($utilisateur);
        }
        $manager->flush();
    }
}
