# OPTIC

OPTIC est une plateforme web de reservation multi-services qui permet aux clients de trouver un etablissement, consulter ses disponibilites et reserver un rendez-vous en ligne.

Le projet couvre plusieurs univers metier: restaurant, coiffure, sante, sport, automobile et bien-etre. Il inclut aussi des espaces dedies aux professionnels, aux managers d'etablissement et aux administrateurs plateforme.

## Fonctionnalites

- Recherche d'etablissements par nom, ville et categorie
- Consultation publique des fiches etablissements
- Reservation de creneaux selon les horaires, les services et la disponibilite des professionnels
- Gestion du compte client, des reservations a venir et passees
- Depot d'avis apres une reservation terminee
- Reponse aux avis cote professionnel
- Reinitialisation du mot de passe par email
- Onboarding partenaire en 3 etapes pour creer un etablissement
- Espace professionnel `/pro` pour consulter ses reservations, son planning et ses avis
- Espace manager `/manager` pour piloter un etablissement
- Back-office admin `/admin` avec EasyAdmin et moderation des comptes

## Roles

- `ROLE_CLIENT`: navigation connectee, reservations, avis, espace compte
- `ROLE_PRO`: espace professionnel `/pro`
- `ROLE_ADMIN_PRO`: gestion complete d'un etablissement via `/manager`
- `ROLE_ADMIN`: administration plateforme via `/admin`

## Stack technique

- PHP 8.4
- Symfony 7.4
- Doctrine ORM + Doctrine Migrations
- PostgreSQL 16
- Twig
- Symfony UX Twig Component, Live Component, Icons et Chart.js
- Asset Mapper / Importmap
- VichUploaderBundle pour les images
- SymfonyCasts Reset Password Bundle
- EasyAdmin
- FrankenPHP + Caddy
- Docker Compose
- PHPUnit + Codeception + Selenium

## Modules principaux

### Cote client

- Page d'accueil marketing avec categories
- Recherche `/store/search`
- Fiches publiques d'etablissements `/establishments/{id}`
- Reservation avec controle des creneaux disponibles
- Espace compte `/account`
- Avis sur reservations passees

### Cote professionnel

- Tableau de bord `/pro/entreprise`
- Vue reservations
- Profil
- Calendrier et planning personnel
- Demandes d'exceptions de planning
- Reponses aux avis clients

### Cote manager

- Tableau de bord par etablissement
- Gestion des services
- Gestion des employes
- Gestion du planning hebdomadaire
- Gestion des demandes de planning
- Gestion des horaires d'ouverture
- Historique, statistiques et parametres
- Upload et suppression d'images d'etablissement

### Cote administration

- CRUD utilisateurs
- CRUD etablissements
- CRUD services
- CRUD reservations
- CRUD avis
- Moderation et activation/desactivation des comptes

## Demarrage rapide

### Prerequis

- Docker
- Docker Compose

### Lancer le projet avec Docker

Le mode Docker est le plus simple pour travailler sur OPTIC. Le conteneur PHP installe automatiquement les dependances Composer et applique les migrations au demarrage.

```bash
docker compose up --build --wait
```

Acces utiles:

- Application: `https://localhost`
- Mailpit: `http://localhost:8025`
- PostgreSQL: `127.0.0.1:5432`

Arreter les services:

```bash
docker compose down
```

Arreter les services et supprimer les volumes:

```bash
docker compose down -v
```

## Lancement sans Docker

Le projet peut aussi tourner en local si PHP 8.4 et PostgreSQL sont deja installes.

Le fichier `.env.local` du depot est configure pour une base PostgreSQL locale sur `127.0.0.1:5432` et desactive l'envoi d'emails.

```bash
composer install
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate
symfony server:start
```

Si vous n'utilisez pas Symfony CLI, servez simplement le dossier `public/` avec votre serveur PHP local.

## Variables d'environnement utiles

- `APP_ENV`
- `APP_SECRET`
- `DATABASE_URL`
- `MAILER_DSN`
- `APP_URL`
- `SERVER_NAME`

## Commandes utiles

Creer un administrateur plateforme:

```bash
docker compose exec php php bin/console app:create-admin admin@example.com 'MotDePasseFort!23'
```

Promouvoir un utilisateur en manager d'etablissement:

```bash
docker compose exec php php bin/console app:promote-manager user@example.com 1
```

Verifier l'etat des migrations:

```bash
docker compose exec php php bin/console doctrine:migrations:status
```

Vider le cache:

```bash
docker compose exec php php bin/console cache:clear
```

## Tests

### PHPUnit

```bash
docker compose exec php php bin/phpunit
```

### Codeception

Tests unitaires:

```bash
docker compose exec php vendor/bin/codecept run Unit
```

Tests fonctionnels:

```bash
docker compose exec php vendor/bin/codecept run Functional
```

Tests d'acceptation:

```bash
docker compose up -d php database selenium
docker compose exec php vendor/bin/codecept run Acceptance
```

Des fixtures de test SQL sont disponibles dans `data/codeception/database_test.sql`.

## Structure du projet

```text
.
├── assets/                  # JS Stimulus et styles via Asset Mapper
├── config/                  # configuration Symfony, securite, bundles
├── data/                    # donnees utilitaires et fixtures SQL
├── frankenphp/              # configuration Docker / Caddy / PHP
├── migrations/              # migrations Doctrine
├── public/                  # point d'entree web et assets publics
├── src/
│   ├── Command/             # commandes console
│   ├── Controller/          # controllers front, pro, manager, admin
│   ├── Entity/              # entites metier
│   ├── Form/                # formulaires Symfony
│   ├── Repository/          # acces aux donnees
│   ├── Security/            # authentification, voter, status checker
│   ├── Service/             # logique metier
│   ├── Twig/Components/     # composants Twig
│   └── Upload/              # gestion des uploads
├── templates/               # vues Twig
└── tests/                   # PHPUnit et Codeception
```

## Fichiers et dossiers importants

- `src/Controller/EstablishmentPageController.php`: fiche publique et reservation
- `src/Controller/PartnerOnboardingController.php`: wizard de creation d'etablissement
- `src/Controller/ProDashboardController.php`: espace professionnel
- `src/Controller/ManagerController.php`: back-office manager
- `src/Controller/Admin/`: administration EasyAdmin
- `config/packages/security.yaml`: roles, firewalls, controle d'acces
- `compose.yaml` et `compose.override.yaml`: execution locale avec Docker

## Infrastructure et docs complementaires

Le projet conserve aussi les documentations d'infrastructure issues du socle Symfony Docker dans le dossier `docs/`, notamment:

- `docs/production.md`
- `docs/xdebug.md`
- `docs/tls.md`
- `docs/troubleshooting.md`

## Licence

Projet distribue sous licence MIT.
