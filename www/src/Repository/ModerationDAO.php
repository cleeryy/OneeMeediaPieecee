<?php
namespace App\Repository;

use App\Core\Database;

class ModerationDAO
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // Méthodes CRUD de base
    public function findById(int $id): ?ModerationEntity;
    public function findAll(): array;
    public function save(ModerationEntity $moderation): ModerationEntity;

    // Méthodes spécifiques à la logique métier
    public function findByModerateur(int $moderateurId): array;
    public function findByTypeAction(string $typeAction): array;
    public function findByCibleUtilisateur(int $utilisateurId): array;
    public function findByCibleArticle(int $articleId): array;
    public function findByCibleCommentaire(int $commentaireId): array;
    public function findRecentActions(int $limit = 10): array;
}
