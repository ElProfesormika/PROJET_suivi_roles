# Missions IUT Roanne

Application web de gestion et de suivi des missions du personnel enseignant de l'Université Jean Monnet, IUT de Roanne.

Le registre papier (date, nature, conducteur, personnes transportées, trajet, horaires, kilométrages, remarques) est remplacé par une application PHP 8 / MySQL, en architecture MVC.

## Fonctionnalités

- Connexion, sessions et rôles `enseignant`, `responsable`, `admin`
- Mots de passe hashés (`password_hash` / `password_verify`)
- Requêtes préparées PDO
- Jeton CSRF sur tous les formulaires POST
- Création, consultation, modification et suppression des missions
- Personnes transportées (plusieurs par mission)
- Validation, refus ou remise en attente, avec commentaire
- Distance calculée : kilométrage retour moins kilométrage départ
- Recherche, filtres, pagination et export CSV
- Tableau de bord, statistiques et graphiques
- Changement de mot de passe et gestion des utilisateurs
- Interface Bootstrap 5, responsive, avec impression d'une mission

## Rôles

| Action | Enseignant | Responsable | Admin |
| --- | --- | --- | --- |
| Se connecter, changer son mot de passe | Oui | Oui | Oui |
| Créer une mission | Oui | Oui | Oui |
| Voir et modifier ses missions | Oui | Oui | Oui |
| Voir et modifier toutes les missions | Non | Oui | Oui |
| Valider, refuser, supprimer | Non | Oui | Oui |
| Statistiques personnelles | Oui | Oui | Oui |
| Statistiques globales | Non | Oui | Oui |
| Gérer les utilisateurs | Non | Non | Oui |

Un enseignant ne peut pas ouvrir la mission d'un autre en changeant l'adresse. Une mission validée ne peut plus être modifiée par l'enseignant. Le contrôle est fait côté serveur.

## Base de données

Fichier : `database/database.sql`

Tables :

- `personne`
- `types_missions`
- `vehicule`
- `trajet`
- `accompagner`

La distance n'est pas stockée. Elle est calculée par `kilometrageRetour - kilometrageDepart` quand le compteur a été relevé. Le type du véhicule est celui du registre (Megane, 3008, Velo). Le type Velo n'a pas de compteur.

Le registre historique du professeur (2012-2018) se charge avec `php database/import_legacy.php` après l'installation du schéma. Les trajets déjà effectués sont importés comme missions validées.

Comptes initiaux, à changer dès la première connexion :

| Rôle | Email | Mot de passe |
| --- | --- | --- |
| Administrateur | admin@ujm-roanne.fr | Admin123! |
| Responsable | responsable@ujm-roanne.fr | Responsable123! |
| Enseignants du registre | adresse `@univ-st-etienne.fr` | Enseignant123! |

## Installation locale avec XAMPP

1. Installer XAMPP (Apache et MySQL).
2. Copier ce dossier dans `C:\xampp\htdocs\mission-ujm-mvc`.
3. Démarrer Apache et MySQL.
4. Ouvrir `http://localhost/phpmyadmin`.
5. Créer une base `mission_ujm`, interclassement `utf8mb4_unicode_ci`.
6. Sélectionner cette base, puis importer `database/database.sql`.
7. Vérifier `config/config.php` :

```php
'db' => [
    'host' => 'localhost',
    'port' => 3306,
    'name' => 'mission_ujm',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
],
```

8. Ouvrir `http://localhost/mission-ujm-mvc/login`.

## Installation locale sous Linux (LAMP)

Sur cette machine, LAMP est déjà installé et la base est importée.

| Composant | Version |
| --- | --- |
| Apache | 2.4 |
| MySQL | 8.0 |
| PHP | 8.3, avec `pdo_mysql` et `mbstring` |

Ouvrir [http://localhost/login](http://localhost/login).

La base s'appelle `mission_ujm`. L'application s'y connecte avec l'utilisateur MySQL `mission_ujm` (mot de passe dans `config/config.php`). Le compte `root` de MySQL n'a pas de mot de passe : il se connecte par le socket, avec `sudo mysql`.

Sur une autre machine Linux, installer les paquets `apache2`, `mysql-server`, `php`, `php-mysql`, `php-mbstring` et `libapache2-mod-php`, créer la base, importer `database/database.sql`, puis pointer Apache vers le dossier `public/`.

Sans Apache :

```bash
php -S localhost:8000 -t public public/dev-router.php
```

Puis ouvrir `http://localhost:8000/login`.

## Déploiement InfinityFree

1. Créer l'hébergement et la base MySQL dans le panneau.
2. Ouvrir phpMyAdmin, sélectionner la base, importer `database/database.sql`.
   Ne pas lancer `CREATE DATABASE` : la base existe déjà.
3. Copier les paramètres MySQL (hôte, nom, utilisateur, mot de passe).
4. Les placer dans `config/config.php`, ou dans `config/config.local.php` pour ne pas les versionner :

```php
<?php

declare(strict_types=1);

return [
    'app' => [
        'debug' => false,
    ],
    'db' => [
        'host' => 'sqlXXX.infinityfree.com',
        'port' => 3306,
        'name' => 'nom_fourni',
        'user' => 'utilisateur_fourni',
        'pass' => 'mot_de_passe_fourni',
        'charset' => 'utf8mb4',
    ],
];
```

5. Envoyer les fichiers par FTP. Le dossier `public` ne doit pas être le seul dossier envoyé : le code dans `app/` doit rester à côté.
6. Passer `debug` à `false`.
7. Tester `/login`, puis changer le mot de passe administrateur.

Sur InfinityFree, l'hôte MySQL n'est en général pas `localhost`.

## Scénario de démonstration

1. Connexion avec `admin@ujm-roanne.fr`.
2. Tableau de bord.
3. Créer un enseignant, par exemple Jean Dupont, département Informatique.
4. Se déconnecter, se connecter avec cet enseignant.
5. Créer une mission : 07/10/2026, Réunion, Roanne vers Saint-Étienne, 08:15 - 14:40, km 16321 à 16525. La distance affichée est 204 km.
6. Se déconnecter.
7. Connexion avec `responsable@ujm-roanne.fr`.
8. Ouvrir la mission en attente, puis cliquer sur « Valider la mission ».
9. Présenter les statistiques.

## Sécurité à expliquer

- Le mot de passe n'est jamais enregistré en clair.
- Les requêtes SQL passent par PDO et des paramètres, pas par concaténation de `$_POST`.
- Chaque formulaire POST contient un champ `_token`, vérifié avant l'action.
- Le cookie de session est `HttpOnly` et `SameSite=Lax`. Il est `Secure` lorsque le site est servi en HTTPS.
- Les droits sont revérifiés dans les contrôleurs, pas seulement masqués dans le menu.
