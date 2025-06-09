# Documentation de l'API

Cette documentation décrit les endpoints de l'API REST du projet OneMediaPiece.

## Authentification

Toutes les requêtes nécessitant une authentification doivent inclure un token JWT valide dans l'en-tête `Authorization`.

```

Authorization: Bearer

```

### Obtenir un token

```

POST /api/auth/login

```

**Corps de la requête:**

```

{
"email": "utilisateur@exemple.com",
"password": "mot_de_passe"
}

```

**Réponse:**

```

{
"success": true,
"token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
"user": {
"id": 1,
"email": "utilisateur@exemple.com",
"pseudonyme": "Utilisateur",
"type_compte": "redacteur"
}
}

```

## Utilisateurs

### Créer un compte

```

POST /api/utilisateur

```

**Corps de la requête:**

```

{
"email": "nouveau@exemple.com",
"password": "mot_de_passe",
"pseudonyme": "Nouveau"
}

```

**Réponse:**

```

{
"success": true,
"message": "Compte créé avec succès, en attente de validation par un administrateur"
}

```

### Récupérer le profil utilisateur

```

GET /api/utilisateur/{id}

```

**Réponse:**

```

{
"id": 1,
"email": "utilisateur@exemple.com",
"pseudonyme": "Utilisateur",
"date_creation": "2025-01-01T12:00:00",
"type_compte": "redacteur"
}

```

## Articles

### Créer un article

```

POST /api/article

```

**Corps de la requête:**

```

{
"titre": "Mon premier article",
"contenu": "Le contenu de mon article...",
"visibilite": "public"
}

```

**Réponse:**

```

{
"success": true,
"article": {
"id": 1,
"titre": "Mon premier article",
"etat": "en_attente",
"message": "Article créé avec succès, en attente de modération"
}
}

```

### Récupérer un article

```

GET /api/article/{id}

```

**Réponse:**

```

{
"id": 1,
"titre": "Mon premier article",
"contenu": "Le contenu de mon article...",
"date_creation": "2025-05-15T10:30:00",
"date_modification": "2025-05-15T10:30:00",
"etat": "accepte",
"visibilite": "public",
"auteur": {
"id": 1,
"pseudonyme": "Utilisateur"
}
}

```

## Commentaires

### Ajouter un commentaire

```

POST /api/article/{article_id}/commentaire

```

**Corps de la requête:**

```

{
"contenu": "Mon commentaire sur cet article"
}

```

**Réponse:**

```

{
"success": true,
"commentaire": {
"id": 1,
"contenu": "Mon commentaire sur cet article",
"etat": "en_attente",
"message": "Commentaire créé avec succès, en attente de modération"
}
}

```

### Récupérer les commentaires d'un article

```

GET /api/article/{article_id}/commentaire

```

**Réponse:**

```

{
"commentaires": [
{
"id": 1,
"contenu": "Premier commentaire",
"date_creation": "2025-05-15T11:00:00",
"etat": "accepte",
"auteur": {
"id": 1,
"pseudonyme": "Utilisateur"
}
},
{
"id": 2,
"contenu": "Deuxième commentaire",
"date_creation": "2025-05-15T11:05:00",
"etat": "accepte",
"auteur": {
"id": 2,
"pseudonyme": "Autre utilisateur"
}
}
]
}

```

## Modération

### Modérer un article

```

PUT /api/moderation/article/{id}

```

**Corps de la requête:**

```

{
"action": "accepte|refuse|efface",
"description": "Raison de la modération (si refusé ou effacé)"
}

```

**Réponse:**

```

{
"success": true,
"message": "Article modéré avec succès"
}

```

### Modérer un commentaire

```

PUT /api/moderation/commentaire/{id}

```

**Corps de la requête:**

```

{
"action": "accepte|refuse|efface",
"description": "Raison de la modération (si refusé ou effacé)"
}

```

**Réponse:**

```

{
"success": true,
"message": "Commentaire modéré avec succès"
}

```

### Signaler un utilisateur

```

POST /api/moderation/utilisateur/{id}/signaler

```

**Corps de la requête:**

```

{
"description": "Raison du signalement"
}

```

**Réponse:**

```

{
"success": true,
"message": "Utilisateur signalé avec succès"
}

```

### Bannir un utilisateur (admin uniquement)

```

PUT /api/moderation/utilisateur/{id}/bannir

```

**Corps de la requête:**

```

{
"description": "Raison du bannissement"
}

```

**Réponse:**

```

{
"success": true,
"message": "Utilisateur banni avec succès"
}

```
