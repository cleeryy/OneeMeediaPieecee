<?php
namespace App\Repository;

use App\Core\Database;
use App\Entity\ArticleEntity;

class ArticleDAO
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // Méthodes CRUD de base
    public function findById(int $id): ?ArticleEntity
    {
        $query = "SELECT * FROM Article WHERE id = :id";
        $params = ['id' => $id];

        $article = $this->db->queryOne($query, $params);

        if (!$article) {
            return null;
        }

        $articleEntity = new ArticleEntity();
        $articleEntity->hydrate($article);

        return $articleEntity;
    }
    public function findAll(array $filtres = []): array
    {
        $query = "SELECT * FROM Article";
        $params = [];

        $query .= " ORDER BY id DESC";

        $articles = $this->db->query($query, $params);

        $articlesEntities = [];

        foreach ($articles as $article) {
            $articleEntity = new ArticleEntity();
            $articleEntity->hydrate($article);
            $articlesEntities[] = $articleEntity;
        }

        return $articlesEntities;
    }
    public function save(ArticleEntity $article): ArticleEntity;
    public function delete(int $id): bool; // Marque comme effacé

    // Méthodes spécifiques à la logique métier
    public function findByUtilisateur(int $utilisateurId): array
    {
        $query = "SELECT * FROM Article WHERE utilisateur_id = :utilisateur_id";
        $params = ['utilisateur_id' => $utilisateurId];

        $query .= " ORDER BY id DESC";

        $articles = $this->db->query($query, $params);

        $articlesEntities = [];

        foreach ($articles as $article) {
            $articleEntity = new ArticleEntity();
            $articleEntity->hydrate($article);
            $articlesEntities[] = $articleEntity;
        }

        return $articlesEntities;
    }
    public function findByEtat(string $etat): array
    {
        $query = "SELECT * FROM Article WHERE etat = :etat";
        $params = ['etat' => $etat];

        $query .= " ORDER BY id DESC";

        $articles = $this->db->query($query, $params);

        $articlesEntities = [];

        foreach ($articles as $article) {
            $articleEntity = new ArticleEntity();
            $articleEntity->hydrate($article);
            $articlesEntities[] = $articleEntity;
        }

        return $articlesEntities;
    }
    public function findEnAttente(): array  // Articles en attente de modération
    {
        $query = "SELECT * FROM Article";
        $params = [];

        $query .= " ORDER BY id DESC";

        $articles = $this->db->query($query, $params);

        $articlesEntities = [];

        foreach ($articles as $article) {
            $articleEntity = new ArticleEntity();
            $articleEntity->hydrate($article);
            $articlesEntities[] = $articleEntity;
        }

        return $articlesEntities;
    }
    public function findPublic(): array  // Articles publics et acceptés
    {
        $query = "SELECT * FROM Article";
        $params = [];

        $query .= " ORDER BY id DESC";

        $articles = $this->db->query($query, $params);

        $articlesEntities = [];

        foreach ($articles as $article) {
            $articleEntity = new ArticleEntity();
            $articleEntity->hydrate($article);
            $articlesEntities[] = $articleEntity;
        }

        return $articlesEntities;
    }
    public function findPrive(): array // Articles privés et acceptés
    {
        $query = "SELECT * FROM Article";
        $params = [];

        $query .= " ORDER BY id DESC";

        $articles = $this->db->query($query, $params);

        $articlesEntities = [];

        foreach ($articles as $article) {
            $articleEntity = new ArticleEntity();
            $articleEntity->hydrate($article);
            $articlesEntities[] = $articleEntity;
        }

        return $articlesEntities;
    }
    public function changerEtat(int $id, string $nouvelEtat): bool;
    public function countByUtilisateur(int $utilisateurId): int;
    public function countByEtat(string $etat): int;
}
