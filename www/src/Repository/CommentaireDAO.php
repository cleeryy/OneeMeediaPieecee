<?php
namespace App\Repository;

use App\Core\Database;
use App\Entity\CommentaireEntity;
use InvalidArgumentException;
use RuntimeException;

class CommentaireDAO
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Crée une entité Commentaire à partir des données de la base
     * @param array $data
     * @return CommentaireEntity
     */
    private function createEntityFromData(array $data): CommentaireEntity
    {
        $entity = new CommentaireEntity();
        return $entity->hydrate($data);
    }

    /**
     * Crée un tableau d'entités Commentaire à partir d'un tableau de données
     * @param array $dataArray
     * @return array
     */
    private function createEntitiesFromDataArray(array $dataArray): array
    {
        $entities = [];
        foreach ($dataArray as $data) {
            $entities[] = $this->createEntityFromData($data);
        }
        return $entities;
    }

    /**
     * Log une requête pour debug
     * @param string $query
     * @param array $params
     */
    private function logQuery(string $query, array $params = []): void
    {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("CommentaireDAO Query: $query | Params: " . json_encode($params));
        }
    }

    /**
     * Trouve un commentaire par son ID
     * @param int $id
     * @return CommentaireEntity|null
     */
    public function findById(int $id): ?CommentaireEntity
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("ID invalide");
        }

        $query = "SELECT * FROM Commentaire WHERE id = :id";
        $params = ['id' => $id];
        $this->logQuery($query, $params);

        $commentaireData = $this->db->queryOne($query, $params);

        if (!$commentaireData) {
            return null;
        }

        return $this->createEntityFromData($commentaireData);
    }

    /**
     * Trouve tous les commentaires
     * @return array
     */
    public function findAll(): array
    {
        $query = "SELECT * FROM Commentaire ORDER BY date_creation DESC";
        $this->logQuery($query);

        $commentairesData = $this->db->query($query);
        return $this->createEntitiesFromDataArray($commentairesData);
    }

    /**
     * Trouve les commentaires avec pagination
     * @param int $page
     * @param int $perPage
     * @param array $filtres
     * @return array
     */
    public function findAllPaginated(int $page = 1, int $perPage = 10, array $filtres = []): array
    {
        $offset = ($page - 1) * $perPage;

        $query = "SELECT * FROM Commentaire";
        $params = [];
        $conditions = [];

        if (!empty($filtres['etat'])) {
            $conditions[] = "etat = :etat";
            $params['etat'] = $filtres['etat'];
        }

        if (!empty($filtres['article_id'])) {
            $conditions[] = "article_id = :article_id";
            $params['article_id'] = $filtres['article_id'];
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " ORDER BY date_creation DESC LIMIT " . (int) $perPage . " OFFSET " . (int) $offset;
        $this->logQuery($query, $params);

        $commentairesData = $this->db->query($query, $params);
        return $this->createEntitiesFromDataArray($commentairesData);
    }

    /**
     * Enregistre un commentaire (création ou mise à jour)
     * @param CommentaireEntity $commentaire
     * @return CommentaireEntity
     */
    public function save(CommentaireEntity $commentaire): CommentaireEntity
    {
        try {
            $this->db->beginTransaction();

            if ($commentaire->getId()) {
                $result = $this->update($commentaire);
            } else {
                $result = $this->insert($commentaire);
            }

            $this->db->commit();
            return $result;

        } catch (\Exception $e) {
            $this->db->rollback();
            throw new RuntimeException("Erreur lors de la sauvegarde du commentaire: " . $e->getMessage());
        }
    }

    /**
     * Marque un commentaire comme effacé (soft delete)
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->changerEtat($id, CommentaireEntity::ETAT_EFFACE);
    }

    /**
     * Trouve les commentaires d'un article avec filtre optionnel sur l'état
     * @param int $articleId
     * @param string|null $etat
     * @return array
     */
    public function findByArticle(int $articleId, ?string $etat = null): array
    {
        if ($articleId <= 0) {
            throw new InvalidArgumentException("ID article invalide");
        }

        $query = "SELECT * FROM Commentaire WHERE article_id = :article_id";
        $params = ['article_id' => $articleId];

        if ($etat !== null) {
            $query .= " AND etat = :etat";
            $params['etat'] = $etat;
        }

        $query .= " ORDER BY date_creation ASC";
        $this->logQuery($query, $params);

        $commentairesData = $this->db->query($query, $params);
        return $this->createEntitiesFromDataArray($commentairesData);
    }

    /**
     * Trouve les commentaires d'un utilisateur
     * @param int $utilisateurId
     * @return array
     */
    public function findByUtilisateur(int $utilisateurId): array
    {
        if ($utilisateurId <= 0) {
            throw new InvalidArgumentException("ID utilisateur invalide");
        }

        $query = "SELECT * FROM Commentaire WHERE utilisateur_id = :utilisateur_id ORDER BY date_creation DESC";
        $params = ['utilisateur_id' => $utilisateurId];
        $this->logQuery($query, $params);

        $commentairesData = $this->db->query($query, $params);
        return $this->createEntitiesFromDataArray($commentairesData);
    }

    /**
     * Trouve les commentaires par état
     * @param string $etat
     * @return array
     */
    public function findByEtat(string $etat): array
    {
        $validEtats = [
            CommentaireEntity::ETAT_ACCEPTE,
            CommentaireEntity::ETAT_EN_ATTENTE,
            CommentaireEntity::ETAT_EFFACE,
            CommentaireEntity::ETAT_REFUSE
        ];

        if (!in_array($etat, $validEtats)) {
            throw new InvalidArgumentException("État invalide: $etat");
        }

        $query = "SELECT * FROM Commentaire WHERE etat = :etat ORDER BY date_creation DESC";
        $params = ['etat' => $etat];
        $this->logQuery($query, $params);

        $commentairesData = $this->db->query($query, $params);
        return $this->createEntitiesFromDataArray($commentairesData);
    }

    /**
     * Trouve les commentaires en attente de modération
     * @return array
     */
    public function findEnAttente(): array
    {
        return $this->findByEtat(CommentaireEntity::ETAT_EN_ATTENTE);
    }

    /**
     * Trouve les commentaires récents
     * @param int $limit
     * @return array
     */
    public function findRecentCommentaires(int $limit = 10): array
    {
        $query = "SELECT * FROM Commentaire WHERE etat = :etat ORDER BY date_creation DESC LIMIT " . (int) $limit;
        $params = ['etat' => CommentaireEntity::ETAT_ACCEPTE];
        $this->logQuery($query, $params);

        $commentairesData = $this->db->query($query, $params);
        return $this->createEntitiesFromDataArray($commentairesData);
    }

    /**
     * Change l'état d'un commentaire
     * @param int $id
     * @param string $nouvelEtat
     * @return bool
     */
    public function changerEtat(int $id, string $nouvelEtat): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("ID invalide");
        }

        $validEtats = [
            CommentaireEntity::ETAT_ACCEPTE,
            CommentaireEntity::ETAT_EN_ATTENTE,
            CommentaireEntity::ETAT_EFFACE,
            CommentaireEntity::ETAT_REFUSE
        ];

        if (!in_array($nouvelEtat, $validEtats)) {
            throw new InvalidArgumentException("État invalide: $nouvelEtat");
        }

        if (!$this->findById($id)) {
            throw new RuntimeException("Commentaire introuvable avec l'ID: $id");
        }

        $query = "UPDATE Commentaire SET etat = :etat, date_modification = NOW() WHERE id = :id";
        $params = ['id' => $id, 'etat' => $nouvelEtat];
        $this->logQuery($query, $params);

        return $this->db->execute($query, $params) > 0;
    }

    /**
     * Compte le nombre de commentaires d'un article (seulement les acceptés)
     * @param int $articleId
     * @return int
     */
    public function countByArticle(int $articleId): int
    {
        $query = "SELECT COUNT(*) as count FROM Commentaire WHERE article_id = :article_id AND etat = :etat";
        $params = [
            'article_id' => $articleId,
            'etat' => CommentaireEntity::ETAT_ACCEPTE
        ];
        $this->logQuery($query, $params);

        $result = $this->db->queryOne($query, $params);
        return (int) $result['count'];
    }

    /**
     * Compte le nombre de commentaires d'un utilisateur
     * @param int $utilisateurId
     * @return int
     */
    public function countByUtilisateur(int $utilisateurId): int
    {
        $query = "SELECT COUNT(*) as count FROM Commentaire WHERE utilisateur_id = :utilisateur_id";
        $params = ['utilisateur_id' => $utilisateurId];
        $this->logQuery($query, $params);

        $result = $this->db->queryOne($query, $params);
        return (int) $result['count'];
    }

    /**
     * Compte le nombre de commentaires par état
     * @param string $etat
     * @return int
     */
    public function countByEtat(string $etat): int
    {
        $query = "SELECT COUNT(*) as count FROM Commentaire WHERE etat = :etat";
        $params = ['etat' => $etat];
        $this->logQuery($query, $params);

        $result = $this->db->queryOne($query, $params);
        return (int) $result['count'];
    }

    /**
     * Trouve les commentaires avec informations d'auteur et d'article (jointures)
     * @param int $limit
     * @return array
     */
    public function findWithDetails(int $limit = 50): array
    {
        $query = "SELECT c.*, u.pseudonyme as auteur_pseudonyme, a.titre as article_titre 
                  FROM Commentaire c 
                  LEFT JOIN Utilisateur u ON c.utilisateur_id = u.id 
                  LEFT JOIN Article a ON c.article_id = a.id 
                  WHERE c.etat = :etat 
                  ORDER BY c.date_creation DESC 
                  LIMIT " . (int) $limit;

        $params = ['etat' => CommentaireEntity::ETAT_ACCEPTE];
        $this->logQuery($query, $params);

        return $this->db->query($query, $params);
    }

    /**
     * Insère un nouveau commentaire
     * @param CommentaireEntity $commentaire
     * @return CommentaireEntity
     */
    private function insert(CommentaireEntity $commentaire): CommentaireEntity
    {
        $query = "INSERT INTO Commentaire (contenu, etat, utilisateur_id, article_id, date_creation, date_modification) 
                  VALUES (:contenu, :etat, :utilisateur_id, :article_id, NOW(), NOW())";

        $params = [
            'contenu' => $commentaire->getContenu(),
            'etat' => $commentaire->getEtat(),
            'utilisateur_id' => $commentaire->getUtilisateurId(),
            'article_id' => $commentaire->getArticleId()
        ];

        $this->logQuery($query, $params);
        $this->db->execute($query, $params);
        $commentaire->setId($this->db->lastInsertId());

        return $commentaire;
    }

    /**
     * Met à jour un commentaire existant
     * @param CommentaireEntity $commentaire
     * @return CommentaireEntity
     */
    private function update(CommentaireEntity $commentaire): CommentaireEntity
    {
        $query = "UPDATE Commentaire SET 
                    contenu = :contenu, 
                    etat = :etat, 
                    date_modification = NOW() 
                  WHERE id = :id";

        $params = [
            'id' => $commentaire->getId(),
            'contenu' => $commentaire->getContenu(),
            'etat' => $commentaire->getEtat()
        ];

        $this->logQuery($query, $params);
        $this->db->execute($query, $params);

        return $commentaire;
    }
}
