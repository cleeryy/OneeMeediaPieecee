# Roadmap du projet OneMediaPiece

## Phase 1: Analyse et planification

**Semaine 1-2**

- [x] **Analyse du cahier des charges**
  - [x] Identifier les fonctionnalités essentielles et bonus
  - [x] Clarifier les points vagues du cahier des charges
  - [x] Lister les questions à poser au professeur
- [x] **Modélisation des données**
  - [x] Concevoir le MCD (Modèle Conceptuel de Données)
  - [x] Élaborer le MLD (Modèle Logique de Données)
  - [x] Définir les relations entre les entités
- [x] **Conception UX/UI**
  - [x] Créer des wireframes pour les différentes vues
  - [x] Définir la charte graphique
  - [x] Concevoir les maquettes des interfaces

## Phase 2: Mise en place de l'infrastructure

**Semaine 3**

- [x] **Configuration de l'environnement LEMP**
  - [x] Installation et configuration de Linux
  - [x] Configuration de Nginx comme serveur web
  - [x] Installation et paramétrage de MariaDB
  - [x] Configuration de PHP-FPM
- [x] **Mise en place de la structure du projet**
  - [x] Création de l'arborescence de fichiers conforme à l'architecture 4-tiers
  - [x] Configuration du système de routage
  - [x] Mise en place du point d'entrée unique (index.php)

## Phase 3: Développement du socle technique

**Semaine 4**

- [x] **Implémentation de la couche Entity**
  - [x] Création des classes UtilisateurEntity, ArticleEntity, CommentaireEntity
  - [x] Implémentation des getters/setters et méthodes de conversion
- [x] **Développement de la couche DAO**
  - [x] Création des classes DAO pour chaque entité
  - [x] Implémentation des méthodes CRUD
  - [x] Configuration de la connexion à la base de données
- [x] **Mise en place de la couche Service**
  - [x] Développement des règles métier
  - [x] Implémentation des services pour chaque fonctionnalité
- [x] **Création de la couche Controller**
  - [x] Mise en place des contrôleurs pour chaque type de requête
  - [x] Implémentation du routage dans index.php

## Phase 4: Développement des fonctionnalités - Utilisateurs

**Semaine 5**

- [x] **Système d'authentification**
  - [x] Inscription avec email et mot de passe
  - [x] Connexion et gestion des sessions
  - [x] Protection des routes selon le rôle
- [ ] **Gestion des utilisateurs**
  - [ ] Validation des comptes par administrateur
  - [ ] Attribution et modification des rôles
  - [ ] Système de bannissement et signalement

## Phase 5: Développement des fonctionnalités - Articles

**Semaine 6**

- [x] **Gestion des articles**
  - [x] Création et modification d'articles
  - [x] Système de modération des articles
  - [x] Gestion de la visibilité (public/privé)
- [x] **Interface de publication**
  - [x] Formulaire de création d'article
  - [x] Éditeur de contenu
  - [x] Interface de gestion des articles

## Phase 6: Développement des fonctionnalités - Commentaires

**Semaine 7**

- [x] **Système de commentaires**
  - [x] Création et affichage des commentaires
  - [x] Implémentation de l'accordion pour afficher les commentaires
  - [x] Modération des commentaires
- [x] **Fonctionnalités de modération**
  - [x] Circuit de validation des contenus
  - [x] Interface de modération pour administrateurs et modérateurs
  - [ ] Système de notification pour les actions de modération

## Phase 7: Implémentation des fonctionnalités bonus

**Semaine 8**

- [ ] **Traçabilité des actions**
  - [ ] Historique des modérations d'articles
  - [ ] Historique des modérations de commentaires
  - [ ] Suivi des signalements d'utilisateurs
  - [ ] Traçage des suppressions de comptes
- [ ] **Optimisations et améliorations**
  - [ ] Mise en cache pour améliorer les performances
  - [ ] Pagination des articles et commentaires
  - [ ] Recherche et filtrage des contenus

## Phase 8: Tests et déploiement

**Semaine 9**

- [x] **Tests**
  - [x] Tests unitaires des différentes fonctionnalités
  - [x] Tests d'intégration entre les couches
  - [ ] Tests utilisateurs et correction des bugs
- [x] **Préparation au déploiement**
  - [x] Configuration de l'environnement de production
  - [ ] Optimisation des performances
  - [ ] Vérification de la sécurité
- [x] **Déploiement**
  - [x] Mise en production sur un serveur web
  - [x] Tests post-déploiement
  - [x] Documentation du projet
