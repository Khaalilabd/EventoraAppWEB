# Nom du Projet
EventoraAppWEB

## Description
EventoraAppWEB est une plateforme complète de gestion d'événements qui simplifie l'organisation, la réservation et la participation à des événements de tous types. Développée par une équipe de 5 étudiants, cette application web vise à révolutionner la manière dont les événements sont créés, gérés et vécus par les utilisateurs.

* **Son objectif** : Faciliter la gestion d'événements et améliorer l'expérience utilisateur pour les organisateurs et les participants, en offrant une interface intuitive et des fonctionnalités avancées.
* **Le problème qu'il résout** : Éliminer la complexité de l'organisation d'événements en centralisant toutes les fonctionnalités nécessaires dans une seule application, réduisant ainsi le besoin d'utiliser plusieurs outils séparés.
* **Ses principales fonctionnalités** :
  * Gestion des membres et authentification multiple (locale et OAuth Google)
  * Gestion de packs et services d'événements personnalisables
  * Système de réservation avancé (standard et personnalisée)
  * Intégration de paiement sécurisé avec Stripe
  * Gestion des réclamations et système de feedback détaillé
  * Notifications multicanal (email via Brevo/Sendinblue, SMS via Twilio)
  * Intégration avec Google Calendar pour synchronisation d'événements
  * Génération de codes QR pour vérification des entrées
  * Interface d'administration complète et sécurisée
  * Support multilingue (Français, Anglais)

## Table des Matières
- [Installation](#installation)
- [Utilisation](#utilisation)
- [Technologies](#technologies)
- [Architecture du Projet](#architecture-du-projet)
- [Fonctionnalités Détaillées](#fonctionnalités-détaillées)
- [API et Intégrations](#api-et-intégrations)
- [Équipe de Développement](#équipe-de-développement)
- [Contributions](#contributions)
- [Licence](#licence)
- [Contact](#contact)

## Installation

1. Clonez le repository :
   ```bash
   git clone https://github.com/equipe-eventora/EventoraAppWEB.git
   cd EventoraAppWEB
   ```

2. Installez les dépendances :
   ```bash
   composer install
   npm install
   ```

3. Configurez votre environnement :
   * Créez un fichier `.env.local` à partir du fichier `.env` :
     ```bash
     cp .env .env.local
     ```
   * Configurez les variables d'environnement suivantes dans votre fichier `.env.local` :
     ```
     # Base de données
     DATABASE_URL="mysql://user:password@127.0.0.1:3306/eventora_db?serverVersion=8.0"
     
     # Clés API des services
     GOOGLE_CLIENT_ID=votre_client_id
     GOOGLE_CLIENT_SECRET=votre_client_secret
     STRIPE_KEY=votre_cle_publique
     STRIPE_SECRET=votre_cle_secrete
     BREVO_API_KEY=votre_cle_api
     TWILIO_ACCOUNT_SID=votre_sid
     TWILIO_AUTH_TOKEN=votre_token
     TWILIO_PHONE_NUMBER=votre_numero
     ```

4. Créez et migrez la base de données :
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. Chargez les données de démonstration (optionnel) :
   ```bash
   php bin/console doctrine:fixtures:load
   ```

6. Compilez les assets :
   ```bash
   npm run build
   ```

7. Lancez le serveur de développement :
   ```bash
   symfony serve
   ```

8. Accédez à l'application dans votre navigateur à l'adresse `http://localhost:8000`

## Utilisation

### Installation de PHP

Pour utiliser ce projet, vous devez installer PHP 8.1 ou supérieur. Voici les étapes :

1. Téléchargez PHP à partir du site officiel : [PHP - Téléchargement](https://www.php.net/downloads.php).

2. Installez PHP en suivant les instructions spécifiques à votre système d'exploitation :
   - Pour Windows, vous pouvez utiliser [XAMPP](https://www.apachefriends.org/fr/index.html) ou [WampServer](https://www.wampserver.com/).
   - Pour macOS, vous pouvez utiliser [Homebrew](https://brew.sh/), puis exécuter la commande suivante dans le terminal :
     ```bash
     brew install php@8.1
     ```
   - Pour Linux, vous pouvez installer PHP via le gestionnaire de paquets. Par exemple, sur Ubuntu :
     ```bash
     sudo apt update
     sudo apt install php8.1 php8.1-common php8.1-mysql php8.1-xml php8.1-curl php8.1-gd php8.1-mbstring php8.1-zip
     ```

3. Vérifiez l'installation de PHP en exécutant la commande suivante dans votre terminal :
   ```bash
   php -v
   ```

### Accès à l'application

Après installation, vous pouvez accéder aux différents espaces :

1. **Espace Public** : Accessible à l'URL racine (`/`)
   * Consultation des événements disponibles
   * Inscription et connexion
   * Recherche d'événements

2. **Espace Membre** : Accessible après connexion (`/membre`)
   * Gestion de profil
   * Réservation d'événements
   * Consultation des réservations
   * Paiements

3. **Espace Admin** : Accessible pour les administrateurs (`/admin`)
   * Gestion des utilisateurs
   * Création et édition d'événements
   * Statistiques et rapports
   * Configuration du système

### Comptes de démonstration

Si vous avez chargé les fixtures, vous pouvez vous connecter avec les comptes suivants :

* **Administrateur** :
  * Email : admin@eventora.com
  * Mot de passe : admin123

* **Utilisateur standard** :
  * Email : user@example.com
  * Mot de passe : user123

## Technologies

Le projet utilise un stack technologique moderne et robuste :

* **Backend** :
  * **Framework** : Symfony 6.4
  * **Base de données** : MySQL 8.0 avec Doctrine ORM
  * **API** : API REST avec Symfony
  * **Authentification** : Symfony Security Bundle, OAuth 2.0 (Google)

* **Frontend** :
  * **Templates** : Twig
  * **JavaScript** : Stimulus, Turbo (Hotwire)
  * **CSS** : Bootstrap 5, SCSS
  * **Build tools** : Webpack Encore

* **Intégrations** :
  * **Paiement** : Stripe
  * **Email** : Brevo/Sendinblue
  * **SMS** : Twilio
  * **Calendrier** : Google Calendar API
  * **Communication** : Discord API

* **DevOps** :
  * **CI/CD** : GitHub Actions
  * **Déploiement** : Docker, Symfony CLI
  * **Tests** : PHPUnit, Cypress

* **Outils** :
  * **PDF** : DomPDF
  * **QR Code** : Endroid QR Code
  * **Pagination** : KnpPaginatorBundle
  * **Formulaires** : EasyAdmin

## Architecture du Projet

L'architecture du projet suit les bonnes pratiques de Symfony et le pattern MVC :

```
EventoraAppWEB/
├── assets/                 # Assets frontend (JS, CSS, images)
├── bin/                    # Exécutables (console Symfony)
├── config/                 # Configuration de l'application
├── migrations/             # Migrations de base de données
├── public/                 # Fichiers publics (index.php, robots.txt)
├── src/                    # Code source PHP
│   ├── Command/            # Commandes console
│   ├── Controller/         # Contrôleurs
│   │   ├── Admin/          # Contrôleurs admin
│   │   ├── Users/          # Contrôleurs utilisateurs
│   │   └── ...
│   ├── Entity/             # Entités Doctrine (modèles)
│   ├── EventListener/      # Écouteurs d'événements
│   ├── EventSubscriber/    # Abonnés aux événements
│   ├── Form/               # Types de formulaires
│   ├── Repository/         # Repositories (accès aux données)
│   ├── Security/           # Classes liées à la sécurité
│   └── Service/            # Services métier
├── templates/              # Templates Twig
├── tests/                  # Tests unitaires et fonctionnels
├── translations/           # Fichiers de traduction
└── var/                    # Fichiers variables (cache, logs)
```

### Diagramme de classes simplifié

```
┌─────────────┐     ┌───────────────┐     ┌───────────────┐
│   Membre    │     │     Pack      │     │  TypePack     │
├─────────────┤     ├───────────────┤     ├───────────────┤
│ id          │     │ id            │     │ id            │
│ nom         │     │ titre         │     │ libelle       │
│ prenom      │     │ description   │     │ description   │
│ email       │     │ prix          │     └───────────────┘
│ mdp         │     │ capacite      │            ▲
│ role        │     │ typepack_id   │            │
└─────────────┘     └───────────────┘            │
      │                     ▲                    │
      │                     │                    │
      ▼                     │                    │
┌─────────────┐     ┌───────────────┐   ┌───────────────┐
│  Favoris    │     │ReservationPack│   │ PackService   │
├─────────────┤     ├───────────────┤   ├───────────────┤
│ id          │     │ membre_id     │   │ pack_id       │
│ membre_id   │     │ pack_id       │   │ service_id    │
│ pack_id     │     │ date          │   │ quantite      │
└─────────────┘     │ status        │   └───────────────┘
                    │               └───────────────┘           │
                                               ▲
                                               │
                  ┌───────────────┐   ┌───────────────┐
                  │ Reclamation   │   │   GService    │
                  ├───────────────┤   ├───────────────┤
                  │ id            │   │ id            │
                  │ titre         │   │ nom           │
                  │ description   │   │ description   │
                  │ membre_id     │   │ prix          │
                  │ status        │   │ disponibilite │
                  └───────────────┘   └───────────────┘
```

## Fonctionnalités Détaillées

### Gestion des Membres
* **Inscription et authentification** :
  * Inscription locale avec validation d'email
  * Connexion via Google OAuth 2.0
  * Récupération de mot de passe sécurisée
* **Profils utilisateurs** :
  * Gestion des informations personnelles
  * Téléchargement d'avatar
  * Historique des activités

### Gestion des Événements
* **Packs d'événements** :
  * Création et configuration de packs personnalisés
  * Association de services aux packs
  * Gestion des capacités et disponibilités
* **Services** :
  * Catalogue de services configurable
  * Services standards et sur mesure
  * Tarification flexible

### Système de Réservation
* **Réservation standard** :
  * Sélection de packs prédéfinis
  * Choix de dates et options
  * Paiement intégré
* **Réservation personnalisée** :
  * Configuration sur mesure d'événements
  * Sélection à la carte de services
  * Devis automatisé

### Système de Paiement
* **Intégration Stripe** :
  * Paiement par carte bancaire sécurisé
  * Gestion des remboursements
  * Paiements fractionnés
* **Facturation** :
  * Génération automatique de factures PDF
  * Historique des transactions
  * Exportation comptable

### Support Client
* **Gestion des réclamations** :
  * Soumission de tickets
  * Suivi de statut
  * Assignation aux administrateurs
* **Feedback** :
  * Évaluation post-événement
  * Suggestions d'amélioration
  * Analyses statistiques

### Intégrations
* **Google Calendar** :
  * Synchronisation bidirectionnelle des événements
  * Rappels automatisés
  * Vue calendrier intégrée
* **Notifications** :
  * Alertes email (Brevo/Sendinblue)
  * SMS (Twilio) pour confirmations importantes
  * Notifications dans l'application
* **Discord** :
  * Intégration de chatbot pour support client
  * Notifications d'événements importants
  * Communauté d'utilisateurs

## API et Intégrations

### API REST

EventoraAppWEB expose une API REST pour permettre des intégrations externes :

* **Authentification** : JWT (JSON Web Tokens)
* **Endpoints principaux** :
  * `/api/events` - Gestion des événements
  * `/api/users` - Informations utilisateurs
  * `/api/bookings` - Gestion des réservations

### Webhooks

Des webhooks sont disponibles pour :
* Notifications de paiement Stripe
* Événements de calendrier Google
* Alertes système

## Équipe de Développement

EventoraAppWEB a été développé par une équipe de 5 étudiants passionnés par le développement web et la gestion d'événements :

* **Khalil Abdelmoumen** - Gestion des réclamations et feedback
  * Suivi et traitement des réclamations
  * Implémentation du système de feedback

* **Hedia Snoussi** - Gestion des utilisateurs
  * Authentification et sécurité
  * Gestion des profils et rôles
  * Interface d'administration des utilisateurs

* **Nadhem Hmida** - Gestion des services
  * Catalogue de services et partenaires
  * Tarification et disponibilité
  * Intégration des services aux packs
  * Intégration de paiement

* **Farah Gharbi** - Gestion des packs
  * Création et configuration des packs
  * Association de services aux packs
  * Système de recommendation
  * gestion de la listes des favoris

* **Rayen Trad** - Gestion des réservations
  * Système de réservation standard et personnalisée
  * Workflow de réservation
  

## Contributions

Nous remercions tous ceux qui ont contribué à ce projet !

### Contributeurs

Les personnes suivantes ont contribué à ce projet en ajoutant des fonctionnalités, en corrigeant des bugs ou en améliorant la documentation :

- [Khalil Abdelmoumen](https://github.com/Khaalilabd/EventoraAppWEB/tree/Eventora) - Gestion des réclamations et feedback
- [Hedia Snoussi](https://github.com/Khaalilabd/EventoraAppWEB/tree/UtilisateurV2) - Gestion des utilisateurs
- [Nadhem Hmida](https://github.com/Khaalilabd/EventoraAppWEB/tree/Service) - Gestion des services
- [Farah Gharbi](https://github.com/Khaalilabd/EventoraAppWEB/tree/GestionPack) - Gestion des packs
- [Rayen Trad](https://github.com/Khaalilabd/EventoraAppWEB/tree/Reservation) - Gestion des réservations

Si vous souhaitez contribuer, suivez les étapes ci-dessous pour faire un *fork*, créer une nouvelle branche et soumettre une *pull request*.

### Comment contribuer ?

1. **Fork le projet** : Allez sur la page GitHub du projet et cliquez sur le bouton *Fork* dans le coin supérieur droit pour créer une copie du projet dans votre propre compte GitHub.

2. **Clonez votre fork** : Clonez le fork sur votre machine locale :
   ```bash
   git clone https://github.com/votre-utilisateur/EventoraAppWEB.git
   cd EventoraAppWEB
   ```

3. **Créez une branche** : Créez une nouvelle branche pour votre fonctionnalité :
   ```bash
   git checkout -b feature/nouvelle-fonctionnalite
   ```

4. **Faites vos modifications** : Implémentez votre fonctionnalité ou correction.

5. **Testez** : Assurez-vous que vos modifications fonctionnent correctement :
   ```bash
   php bin/phpunit
   ```

6. **Committez** : Enregistrez vos modifications :
   ```bash
   git commit -m "Ajout de la fonctionnalité X"
   ```

7. **Poussez** : Envoyez vos modifications vers votre fork :
   ```bash
   git push origin feature/nouvelle-fonctionnalite
   ```

8. **Créez une Pull Request** : Ouvrez une Pull Request depuis votre fork sur GitHub.

### Standards de code

* Suivez les standards PSR-1, PSR-4, PSR-12
* Commentez votre code en français
* Écrivez des tests pour les nouvelles fonctionnalités
* Respectez l'architecture existante du projet

## Licence

Ce projet est sous la licence **MIT**. Pour plus de détails, consultez le fichier [LICENSE](/LICENSE).

### Détails sur la licence MIT

La licence MIT est une licence de logiciel libre et open source qui permet une grande liberté aux utilisateurs. Elle permet :

* L'utilisation commerciale
* La modification
* La distribution
* L'utilisation privée

La seule condition est de conserver l'avis de copyright et la licence dans toutes les copies ou portions substantielles du logiciel.

## Contact

Pour toute question concernant ce projet, vous pouvez contacter l'équipe de développement :

* **Email** : contact@eventora.com
* **Site Web** : https://www.eventora.com
* **GitHub** : https://github.com/equipe-eventora

---

© 2023-2024 Équipe Eventora. Tous droits réservés. 