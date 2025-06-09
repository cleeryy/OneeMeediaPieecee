# Architecture du projet OneMediaPiece

## Vue d'ensemble de l'architecture 4-tiers

Le projet est structuré selon une architecture 4-tiers:

1. **Couche Présentation** - Frontend (HTML/CSS/JavaScript)
2. **Couche Applicative** - Contrôleurs PHP
3. **Couche Métier** - Services PHP implémentant la logique métier
4. **Couche DAO** - Objets d'accès aux données et entités

## Structure du projet

```
/var/www/oneMediaPiece/
├── config/ # Configuration BDD, constantes, etc.
├── public/ # Point d'entrée (index.php) et assets statiques
│ ├── index.php # Unique point d'entrée
│ ├── css/
│ ├── js/
│ └── img/
└── src/ # Code source de l'application
├── Entity/ # Couche 1: Représentation objets des tables
├── Dao/ # Couche 2: Data Access Objects
├── Service/ # Couche 3: Logique métier
└── Controller/ # Couche 4: Contrôleurs d'API
```

## Flux de données

1. Les requêtes sont traitées par `index.php` qui analyse l'URL et la méthode HTTP
2. Le contrôleur approprié est appelé en fonction de la route
3. Le contrôleur valide les données et délègue au service approprié
4. Le service applique les règles métier et utilise les DAO pour accéder aux données
5. Les DAO communiquent avec la base de données et créent des objets Entity
6. Le résultat remonte la chaîne et est formaté en JSON par le contrôleur
7. Le frontend traite la réponse JSON et met à jour l'interface utilisateur
