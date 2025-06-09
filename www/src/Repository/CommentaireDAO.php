<?php
namespace App\Repository;

use App\Core\Database;

class CommentaireDAO
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // Méthodes CRUD de base
    public function findById(int $id): ?CommentaireEntity;
    public function findAll(): array;
    public function save(CommentaireEntity $commentaire): CommentaireEntity;
    public function delete(int $id): bool; // Marque comme effacé

    // Méthodes spécifiques à la logique métier
    public function findByArticle(int $articleId, string $etat = null): array;
    public function findByUtilisateur(int $utilisateurId): array;
    public function findByEtat(string $etat): array;
    public function findEnAttente(): array; // Commentaires en attente de modération
    public function changerEtat(int $id, string $nouvelEtat): bool;
    public function countByArticle(int $articleId): int;
    public function countByUtilisateur(int $utilisateurId): int;
}
