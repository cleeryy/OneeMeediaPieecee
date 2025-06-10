<?php
namespace App\Repository;

use App\Core\Database;
use App\Entity\ModerationEntity;
use InvalidArgumentException;
use RuntimeException;

class ModerationDAO
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Crée une entité Moderation à partir des données de la base
     * @param array $data
     * @return ModerationEntity
     */
    private function createEntityFromData(array $data): ModerationEntity
    {
        $entity = new ModerationEntity();
        return $entity->hydrate($data);
    }

    /**
     * Crée un tableau d'entités Moderation à partir d'un tableau de données
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
            error_log("ModerationDAO Query: $query | Params: " . json_encode($params));
        }
    }

    /**
     * Trouve une action de modération par son ID
     * @param int $id
     * @return ModerationEntity|null
     */
    public function findById(int $id): ?ModerationEntity
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("ID invalide");
        }

        $query = "SELECT * FROM Moderation WHERE id = :id";
        $params = ['id' => $id];
        $this->logQuery($query, $params);

        $moderationData = $this->db->queryOne($query, $params);

        if (!$moderationData) {
            return null;
        }

        return $this->createEntityFromData($moderationData);
    }

    /**
     * Trouve toutes les actions de modération
     * @return array
     */
    public function findAll(): array
    {
        $query = "SELECT * FROM Moderation ORDER BY date_action DESC";
        $this->logQuery($query);

        $moderationsData = $this->db->query($query);
        return $this->createEntitiesFromDataArray($moderationsData);
    }

    /**
     * Trouve les modérations avec pagination
     * @param int $page
     * @param int $perPage
     * @param array $filtres
     * @return array
     */
    public function findAllPaginated(int $page = 1, int $perPage = 10, array $filtres = []): array
    {
        $offset = ($page - 1) * $perPage;

        $query = "SELECT * FROM Moderation";
        $params = [];
        $conditions = [];

        if (!empty($filtres['type_action'])) {
            $conditions[] = "type_action = :type_action";
            $params['type_action'] = $filtres['type_action'];
        }

        if (!empty($filtres['moderateur_id'])) {
            $conditions[] = "moderateur_id = :moderateur_id";
            $params['moderateur_id'] = $filtres['moderateur_id'];
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " ORDER BY date_action DESC LIMIT " . (int) $perPage . " OFFSET " . (int) $offset;
        $this->logQuery($query, $params);

        $moderationsData = $this->db->query($query, $params);
        return $this->createEntitiesFromDataArray($moderationsData);
    }

    /**
     * Enregistre une action de modération (création uniquement)
     * @param ModerationEntity $moderation
     * @return ModerationEntity
     */
    public function save(ModerationEntity $moderation): ModerationEntity
    {
        try {
            $this->db->beginTransaction();

            $query = "INSERT INTO Moderation (type_action, description, moderateur_id, cible_utilisateur_id, cible_article_id, cible_commentaire_id, date_action)
                      VALUES (:type_action, :description, :moderateur_id, :cible_utilisateur_id, :cible_article_id, :cible_commentaire_id, NOW())";

            $params = [
                'type_action' => $moderation->getTypeAction(),
                'description' => $moderation->getDescription(),
                'moderateur_id' => $moderation->getModerateurId(),
                'cible_utilisateur_id' => $moderation->getCibleUtilisateurId(),
                'cible_article_id' => $moderation->getCibleArticleId(),
                'cible_commentaire_id' => $moderation->getCibleCommentaireId()
            ];

            $this->logQuery($query, $params);
            $this->db->execute($query, $params);
            $moderation->setId($this->db->lastInsertId());

            $this->db->commit();
            return $moderation;

        } catch (\Exception $e) {
            $this->db->rollback();
            throw new RuntimeException("Erreur lors de la sauvegarde de la modération: " . $e->getMessage());
        }
    }

    /**
     * Trouve les actions par modérateur
     * @param int $moderateurId
     * @return array
     */
    public function findByModerateur(int $moderateurId): array
    {
        if ($moderateurId <= 0) {
            throw new InvalidArgumentException("ID modérateur invalide");
        }

        $query = "SELECT * FROM Moderation WHERE moderateur_id = :moderateur_id ORDER BY date_action DESC";
        $params = ['moderateur_id' => $moderateurId];
        $this->logQuery($query, $params);

        $moderationsData = $this->db->query($query, $params);
        return $this->createEntitiesFromDataArray($moderationsData);
    }

    /**
     * Trouve les actions par type
     * @param string $typeAction
     * @return array
     */
    public function findByTypeAction(string $typeAction): array
    {
        $validTypes = [
            ModerationEntity::TYPE_REFUS_ARTICLE,
            ModerationEntity::TYPE_REFUS_COMMENTAIRE,
            ModerationEntity::TYPE_SIGNALEMENT,
            ModerationEntity::TYPE_SUPPRESSION_COMPTE
        ];

        if (!in_array($typeAction, $validTypes)) {
            throw new InvalidArgumentException("Type d'action invalide: $typeAction");
        }

        $query = "SELECT * FROM Moderation WHERE type_action = :type_action ORDER BY date_action DESC";
        $params = ['type_action' => $typeAction];
        $this->logQuery($query, $params);

        $moderationsData = $this->db->query($query, $params);
        return $this->createEntitiesFromDataArray($moderationsData);
    }

    /**
     * Trouve les actions ciblant un utilisateur
     * @param int $utilisateurId
     * @return array
     */
    public function findByCibleUtilisateur(int $utilisateurId): array
    {
        if ($utilisateurId <= 0) {
            throw new InvalidArgumentException("ID utilisateur invalide");
        }

        $query = "SELECT * FROM Moderation WHERE cible_utilisateur_id = :utilisateur_id ORDER BY date_action DESC";
        $params = ['utilisateur_id' => $utilisateurId];
        $this->logQuery($query, $params);

        $moderationsData = $this->db->query($query, $params);
        return $this->createEntitiesFromDataArray($moderationsData);
    }

    /**
     * Trouve les actions ciblant un article
     * @param int $articleId
     * @return array
     */
    public function findByCibleArticle(int $articleId): array
    {
        if ($articleId <= 0) {
            throw new InvalidArgumentException("ID article invalide");
        }

        $query = "SELECT * FROM Moderation WHERE cible_article_id = :article_id ORDER BY date_action DESC";
        $params = ['article_id' => $articleId];
        $this->logQuery($query, $params);

        $moderationsData = $this->db->query($query, $params);
        return $this->createEntitiesFromDataArray($moderationsData);
    }

    /**
     * Trouve les actions ciblant un commentaire
     * @param int $commentaireId
     * @return array
     */
    public function findByCibleCommentaire(int $commentaireId): array
    {
        if ($commentaireId <= 0) {
            throw new InvalidArgumentException("ID commentaire invalide");
        }

        $query = "SELECT * FROM Moderation WHERE cible_commentaire_id = :commentaire_id ORDER BY date_action DESC";
        $params = ['commentaire_id' => $commentaireId];
        $this->logQuery($query, $params);

        $moderationsData = $this->db->query($query, $params);
        return $this->createEntitiesFromDataArray($moderationsData);
    }

    /**
     * Trouve les actions récentes
     * @param int $limit
     * @return array
     */
    public function findRecentActions(int $limit = 10): array
    {
        $query = "SELECT * FROM Moderation ORDER BY date_action DESC LIMIT " . (int) $limit;
        $this->logQuery($query);

        $moderationsData = $this->db->query($query);
        return $this->createEntitiesFromDataArray($moderationsData);
    }

    /**
     * Trouve les actions de modération dans une période donnée
     * @param string $dateDebut Format: Y-m-d H:i:s
     * @param string $dateFin Format: Y-m-d H:i:s
     * @return array
     */
    public function findByPeriode(string $dateDebut, string $dateFin): array
    {
        $query = "SELECT * FROM Moderation 
                  WHERE date_action BETWEEN :date_debut AND :date_fin 
                  ORDER BY date_action DESC";

        $params = [
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin
        ];
        $this->logQuery($query, $params);

        $moderationsData = $this->db->query($query, $params);
        return $this->createEntitiesFromDataArray($moderationsData);
    }

    /**
     * Trouve les actions de modération avec jointures pour obtenir des informations complètes
     * @param int $limit
     * @return array
     */
    public function findWithDetails(int $limit = 50): array
    {
        $query = "SELECT m.*, 
                         moderateur.pseudonyme as moderateur_pseudonyme,
                         CASE 
                           WHEN m.cible_utilisateur_id IS NOT NULL THEN u.pseudonyme
                           WHEN m.cible_article_id IS NOT NULL THEN a.titre
                           WHEN m.cible_commentaire_id IS NOT NULL THEN CONCAT('Commentaire #', c.id)
                           ELSE NULL
                         END as cible_nom
                  FROM Moderation m
                  LEFT JOIN Utilisateur moderateur ON m.moderateur_id = moderateur.id
                  LEFT JOIN Utilisateur u ON m.cible_utilisateur_id = u.id
                  LEFT JOIN Article a ON m.cible_article_id = a.id
                  LEFT JOIN Commentaire c ON m.cible_commentaire_id = c.id
                  ORDER BY m.date_action DESC 
                  LIMIT " . (int) $limit;

        $this->logQuery($query);
        return $this->db->query($query);
    }

    /**
     * Trouve les signalements d'utilisateurs non traités
     * @return array
     */
    public function findSignalementsNonTraites(): array
    {
        // Cette méthode retourne tous les signalements récents
        return $this->findByTypeAction(ModerationEntity::TYPE_SIGNALEMENT);
    }

    /**
     * Enregistre un refus d'article avec les détails
     * @param int $articleId
     * @param int $moderateurId
     * @param string $description
     * @return ModerationEntity
     */
    public function enregistrerRefusArticle(int $articleId, int $moderateurId, string $description): ModerationEntity
    {
        $moderation = new ModerationEntity();
        $moderation->setTypeAction(ModerationEntity::TYPE_REFUS_ARTICLE);
        $moderation->setDescription($description);
        $moderation->setModerateurId($moderateurId);
        $moderation->setCibleArticleId($articleId);

        return $this->save($moderation);
    }

    /**
     * Enregistre un refus de commentaire avec les détails
     * @param int $commentaireId
     * @param int $moderateurId
     * @param string $description
     * @return ModerationEntity
     */
    public function enregistrerRefusCommentaire(int $commentaireId, int $moderateurId, string $description): ModerationEntity
    {
        $moderation = new ModerationEntity();
        $moderation->setTypeAction(ModerationEntity::TYPE_REFUS_COMMENTAIRE);
        $moderation->setDescription($description);
        $moderation->setModerateurId($moderateurId);
        $moderation->setCibleCommentaireId($commentaireId);

        return $this->save($moderation);
    }

    /**
     * Enregistre un signalement d'utilisateur
     * @param int $utilisateurId
     * @param int $moderateurId
     * @param string $description
     * @return ModerationEntity
     */
    public function enregistrerSignalement(int $utilisateurId, int $moderateurId, string $description): ModerationEntity
    {
        $moderation = new ModerationEntity();
        $moderation->setTypeAction(ModerationEntity::TYPE_SIGNALEMENT);
        $moderation->setDescription($description);
        $moderation->setModerateurId($moderateurId);
        $moderation->setCibleUtilisateurId($utilisateurId);

        return $this->save($moderation);
    }

    /**
     * Enregistre une suppression de compte
     * @param int $utilisateurId
     * @param int $administrateurId
     * @param string $description
     * @return ModerationEntity
     */
    public function enregistrerSuppressionCompte(int $utilisateurId, int $administrateurId, string $description): ModerationEntity
    {
        $moderation = new ModerationEntity();
        $moderation->setTypeAction(ModerationEntity::TYPE_SUPPRESSION_COMPTE);
        $moderation->setDescription($description);
        $moderation->setModerateurId($administrateurId);
        $moderation->setCibleUtilisateurId($utilisateurId);

        return $this->save($moderation);
    }

    /**
     * Compte le nombre total d'actions de modération
     * @param array $filtres
     * @return int
     */
    public function countAll(array $filtres = []): int
    {
        $query = "SELECT COUNT(*) as count FROM Moderation";
        $params = [];
        $conditions = [];

        if (!empty($filtres['type_action'])) {
            $conditions[] = "type_action = :type_action";
            $params['type_action'] = $filtres['type_action'];
        }

        if (!empty($filtres['moderateur_id'])) {
            $conditions[] = "moderateur_id = :moderateur_id";
            $params['moderateur_id'] = $filtres['moderateur_id'];
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $this->logQuery($query, $params);
        $result = $this->db->queryOne($query, $params);
        return (int) $result['count'];
    }

    /**
     * Compte le nombre d'actions par modérateur
     * @param int $moderateurId
     * @return int
     */
    public function countByModerateur(int $moderateurId): int
    {
        $query = "SELECT COUNT(*) as count FROM Moderation WHERE moderateur_id = :moderateur_id";
        $params = ['moderateur_id' => $moderateurId];
        $this->logQuery($query, $params);

        $result = $this->db->queryOne($query, $params);
        return (int) $result['count'];
    }

    /**
     * Compte le nombre d'actions par type
     * @param string $typeAction
     * @return int
     */
    public function countByTypeAction(string $typeAction): int
    {
        $query = "SELECT COUNT(*) as count FROM Moderation WHERE type_action = :type_action";
        $params = ['type_action' => $typeAction];
        $this->logQuery($query, $params);

        $result = $this->db->queryOne($query, $params);
        return (int) $result['count'];
    }

    /**
     * Supprime définitivement une action de modération (rare, seulement pour nettoyage)
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("ID invalide");
        }

        $query = "DELETE FROM Moderation WHERE id = :id";
        $params = ['id' => $id];
        $this->logQuery($query, $params);

        return $this->db->execute($query, $params) > 0;
    }
}
