# Documentation Conception UX/UI - Projet OneMediaPiece

## Introduction à la conception UX/UI

La conception UX/UI joue un rôle crucial dans le succès de notre blog OneMediaPiece. Une expérience utilisateur fluide et intuitive augmentera l'engagement des utilisateurs et facilitera l'adoption de la plateforme par les rédacteurs, modérateurs et administrateurs.

## Processus de conception UX/UI

### 1. Phase de recherche et d'analyse

**Compréhension des besoins utilisateurs**

- Réaliser des interviews utilisateurs pour différents profils (visiteurs, rédacteurs, modérateurs, administrateurs)
- Identifier les attentes spécifiques pour chaque type d'utilisateur
- Définir les objectifs principaux pour chaque profil

**Création de personas**

- Développer 4 personas correspondant aux différents types d'utilisateurs:
  - Visiteur anonyme
  - Rédacteur
  - Modérateur
  - Administrateur
- Pour chaque persona, définir motivations, frustrations et objectifs

**Cartographie du parcours utilisateur**

- Créer des journey maps pour les parcours principaux:
  - Création de compte et validation
  - Publication et modération d'articles
  - Rédaction et modération de commentaires
  - Gestion des utilisateurs (signalement, bannissement)

### 2. Phase de conception et d'idéation

**Flux utilisateur (User Flows)**

- Concevoir des diagrammes de flux pour les principales actions:
  - Processus d'inscription et validation
  - Création et publication d'un article
  - Système de commentaires et modération
  - Gestion des comptes utilisateurs

**Wireframes**

- Créer des wireframes basse fidélité pour les pages principales:
  - Page d'accueil (liste d'articles)
  - Page d'article avec système de commentaires en accordéon
  - Tableau de bord utilisateur
  - Interface de modération
  - Formulaires (inscription, création d'article, etc.)

### 3. Phase de mise en place

**Prototypage**

- Développer des maquettes haute fidélité basées sur les wireframes
- Créer un prototype interactif pour tester les fonctionnalités clés
- Appliquer le système de design défini (couleurs, typographie, composants)

**Système de design**

- Définir une charte graphique cohérente:
  - Palette de couleurs primaires et secondaires
  - Système typographique
  - Composants d'interface (boutons, champs de formulaire, etc.)
  - États visuels (normal, hover, actif, désactivé)

### 4. Phase de test et d'optimisation

**Tests utilisateurs**

- Organiser des sessions de test avec des utilisateurs représentatifs
- Recueillir les retours sur la facilité d'utilisation
- Identifier les points de friction dans l'expérience

**Itérations et ajustements**

- Analyser les résultats des tests
- Apporter des modifications au design selon les retours
- Tester les modifications pour validation

## Principes de design UI appliqués

### Hiérarchie visuelle et composition

- Utiliser un système de grille cohérent pour l'ensemble du site
- Mettre en évidence les éléments importants par la taille, le poids et la couleur
- Créer un contraste suffisant entre les éléments d'interface et le contenu
- Maintenir une densité d'information équilibrée

### Système de couleurs

- Palette principale:

  - Couleur primaire: #2C3E50 (bleu foncé)
  - Couleur secondaire: #E74C3C (rouge)
  - Couleur d'accent: #3498DB (bleu clair)
  - Fond clair: #ECF0F1
  - Texte: #2C3E50

- Couleurs fonctionnelles:
  - En attente: #F39C12 (orange)
  - Accepté: #2ECC71 (vert)
  - Refusé: #E74C3C (rouge)
  - Effacé: #95A5A6 (gris)

### Typographie

- Titres: Roboto, sans-serif
- Corps de texte: Open Sans, sans-serif
- Hiérarchie typographique claire:
  - H1: 32px, bold
  - H2: 24px, semibold
  - H3: 20px, medium
  - Corps de texte: 16px, regular
  - Texte secondaire: 14px, regular

### Design responsive et adaptatif

- Concevoir en "mobile-first" pour garantir une expérience optimale sur tous les appareils
- Établir des points de rupture (breakpoints) pour les différentes tailles d'écran
- Adapter la navigation et les composants selon le contexte d'utilisation
- S'assurer que les éléments interactifs sont dimensionnés pour une utilisation tactile

## Composants d'interface spécifiques au projet

### Système d'articles

- Carte d'article avec:
  - Image principale (optionnelle)
  - Titre de l'article
  - Extrait du contenu
  - Nom de l'auteur et date de publication
  - Indicateur de statut (pour les rédacteurs et modérateurs)
  - Badge de visibilité (public/privé)

### Système de commentaires

- Composant d'accordéon permettant de:
  - Afficher/masquer les commentaires
  - Indiquer le nombre total de commentaires
  - Présenter les commentaires de façon chronologique
  - Différencier visuellement les commentaires en attente, acceptés, refusés

### Interface de modération

- Tableau de bord avec filtres par:
  - Type de contenu (articles, commentaires)
  - Statut (en attente, acceptés, refusés)
  - Date de création
- Actions de modération clairement identifiées par des icônes et couleurs distinctives

### États visuels des contenus

- Créer un système visuel cohérent pour les différents états:
  - En attente: bordure ou badge orange
  - Accepté: bordure ou badge vert
  - Refusé: bordure ou badge rouge
  - Effacé: opacité réduite et badge gris

## Bonnes pratiques

- **Simplicité et cohérence**: Maintenir une interface épurée et cohérente à travers tout le site
- **Accessibilité**: Assurer un contraste suffisant pour la lisibilité et respecter les standards WCAG
- **Feedback utilisateur**: Fournir des retours visuels pour chaque action (confirmation, erreur)
- **Prévention des erreurs**: Concevoir des formulaires avec validation en temps réel
- **Affordance**: Rendre les éléments interactifs clairement identifiables
