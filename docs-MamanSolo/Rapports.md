*** RAPPORT ***

Mise en place

Nouveau projet créé avec symfony 

Base MySQL connectée 

Trois entités principales :

Utilisateur : nom, email, mot de passe

Situation : situationFamiliale, nbEnfants

Avatar : couleurPeau, couleurCheveux, styleVetement

Relations : (voir fichier UML)
Utilisateur ↔ Situation (1–1)
Utilisateur ↔ Avatar (1–1)

Création de UtilisateurFixtures pour générer 10 profils avec mot de passe haché

Interface

Contrôleur AccueilController et template Twig accueil/index.html.twig

Logo pixel art “MamanSolo” ajouté dans le header comme lien d’accueil

Styles centralisés dans public/css/style.css

Design

Thème en nuances de rose 🌸 (à revoir ensemble)

Layout simple et responsive (PC, tablette, mobile)

Résultat

L’application est fonctionnelle, connectée à MySQL, avec design de base et données de test prêtes.
Prochaine étape : création des formulaires d’inscription et login. 