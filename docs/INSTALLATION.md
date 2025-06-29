# OneMediaPiece - Installation

## Prérequis

- [Docker](https://docs.docker.com/get-docker/) installé
- [Docker Compose](https://docs.docker.com/compose/install/) installé
- Git installé

## Installation

### 1. Cloner le repository

```
git clone https://github.com/votre-username/oneMediaPiece.git
cd oneMediaPiece
```

### 2. Lancer l'application

```
docker-compose up --build -d
```

### 3. Accéder à l'application

- **Application** : [http://localhost](http://localhost)
- **phpMyAdmin** : [http://localhost:8080](http://localhost:8080)

## Configuration

- La base de données est automatiquement initialisée avec le script `sql/init.sql`
- Les variables d'environnement sont configurées dans le fichier `.env`
- Compte admin par défaut : `admin@onemediapiece.com` / `admin123`

## Services

| Service    | Port | Description     |
| ---------- | ---- | --------------- |
| Nginx      | 80   | Serveur web     |
| phpMyAdmin | 8080 | Interface BDD   |
| MariaDB    | 3306 | Base de données |

## Commandes utiles

```
# Arrêter l'application
docker-compose down

# Voir les logs
docker-compose logs -f

# Redémarrer un service
docker-compose restart nginx

# Reconstruire complètement
docker-compose down -v && docker-compose up --build -d
```

## Support

En cas de problème, vérifiez que les ports 80, 3306 et 8080 ne sont pas utilisés par d'autres services.
