<?php
namespace App\Repository;

use App\Core\Database;
use App\Entity\ArticleEntity;
use InvalidArgumentException;
use RuntimeException;

class ArticleDAO
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Crée une entité Article à partir des données de la base
     * @param array $data
     * @return ArticleEntity
     */
    private function createEntityFromData(array $data): ArticleEntity
    {
        $entity = new ArticleEntity();
        return $entity->hydrate($data);
    }

    /**
     * Crée un tableau d'entités Article à partir d'un tableau de données
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
     * Construit les conditions WHERE pour les filtres
     * @param array $filtres
     * @param array $params
     * @return array
     */
    private function buildConditions(array $filtres, array &$params): array
    {
        $conditions = [];

        if (!empty($filtres['etat'])) {
            $conditions[] = "etat = :etat";
            $params['etat'] = $filtres['etat'];
        }

        if (!empty($filtres['visibilite'])) {
            $conditions[] = "visibilite = :visibilite";
            $params['visibilite'] = $filtres['visibilite'];
        }

        if (!empty($filtres['utilisateur_id'])) {
            $conditions[] = "utilisateur_id = :utilisateur_id";
            $params['utilisateur_id'] = $filtres['utilisateur_id'];
        }

        if (!empty($filtres['date_debut'])) {
            $conditions[] = "date_creation >= :date_debut";
            $params['date_debut'] = $filtres['date_debut'];
        }

        if (!empty($filtres['date_fin'])) {
            $conditions[] = "date_creation <= :date_fin";
            $params['date_fin'] = $filtres['date_fin'];
        }

        return $conditions;
    }

    /**
     * Log une requête pour debug
     * @param string $query
     * @param array $params
     */
    private function logQuery(string $query, array $params = []): void
    {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("ArticleDAO Query: $query | Params: " . json_encode($params));
        }
    }

    /**
     * Trouve un article par son ID
     * @param int $id
     * @return ArticleEntity|null
     */
    public function findById(int $id): ?ArticleEntity
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("ID invalide");
        }

        $query = "SELECT * FROM Article WHERE id = :id";
        $params = ['id' => $id];
        $this->logQuery($query, $params);

        $articleData = $this->db->queryOne($query, $params);

        if (!$articleData) {
            return null;
        }

        return $this->createEntityFromData($articleData);
    }

    /**
     * Trouve tous les articles avec filtres optionnels
     * @param array $filtres
     * @return array
     */
    public function findAll(array $filtres = []): array
    {
        $query = "SELECT * FROM Article";
        $params = [];
        $conditions = $this->buildConditions($filtres, $params);

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " ORDER BY date_creation DESC";
        $this->logQuery($query, $params);

        $articlesData = $this->db->query($query, $params);
        return $this->createEntitiesFromDataArray($articlesData);
    }

    /**
     * Trouve les articles avec pagination
     * @param int $page
     * @param int $perPage
     * @param array $filtres
     * @return array
     */
    public function findAllPaginated(int $page = 1, int $perPage = 10, array $filtres = []): array
    {
        $offset = ($page - 1) * $perPage;

        $query = "SELECT * FROM Article";
        $params = [];
        $conditions = $this->buildConditions($filtres, $params);

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " ORDER BY date_creation DESC LIMIT " . (int) $perPage . " OFFSET " . (int) $offset;
        $this->logQuery($query, $params);

        $articlesData = $this->db->query($query, $params);
        return $this->createEntitiesFromDataArray($articlesData);
    }

    /**
     * Compte le nombre total d'articles
     * @param array $filtres
     * @return int
     */
    public function countAll(array $filtres = []): int
    {
        $query = "SELECT COUNT(*) as count FROM Article";
        $params = [];
        $conditions = $this->buildConditions($filtres, $params);

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $this->logQuery($query, $params);
        $result = $this->db->queryOne($query, $params);
        return (int) $result['count'];
    }

    /**
     * Enregistre un article (création ou mise à jour)
     * @param ArticleEntity $article
     * @return ArticleEntity
     */
    public function save(ArticleEntity $article): ArticleEntity
    {
        try {
            $this->db->beginTransaction();

            if ($article->getId()) {
                $result = $this->update($article);
            } else {
                $result = $this->insert($article);
            }

            $this->db->commit();
            return $result;

        } catch (\Exception $e) {
            $this->db->rollback();
            throw new RuntimeException("Erreur lors de la sauvegarde de l'article: " . $e->getMessage());
        }
    }

    /**
     * Marque un article comme effacé (soft delete)
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->changerEtat($id, ArticleEntity::ETAT_EFFACE);
    }

    /**
     * Trouve les articles d'un utilisateur
     * @param int $utilisateurId
     * @return array
     */
    public function findByUtilisateur(int $utilisateurId): array
    {
        if ($utilisateurId <= 0) {
            throw new InvalidArgumentException("ID utilisateur invalide");
        }

        $query = "SELECT * FROM Article WHERE utilisateur_id = :utilisateur_id ORDER BY date_creation DESC";
        $params = ['utilisateur_id' => $utilisateurId];
        $this->logQuery($query, $params);

        $articlesData = $this->db->query($query, $params);
        return $this->createEntitiesFromDataArray($articlesData);
    }

    /**
     * Trouve les articles par état
     * @param string $etat
     * @return array
     */
    public function findByEtat(string $etat): array
    {
        return $this->findAll(['etat' => $etat]);
    }

    /**
     * Trouve les articles en attente de modération
     * @return array
     */
    public function findEnAttente(): array
    {
        return $this->findByEtat(ArticleEntity::ETAT_EN_ATTENTE);
    }

    /**
     * Trouve les articles publics et acceptés
     * @return array
     */
    public function findPublic(): array
    {
        return $this->findAll([
            'etat' => ArticleEntity::ETAT_ACCEPTE,
            'visibilite' => ArticleEntity::VISIBILITE_PUBLIC
        ]);
    }

    /**
     * Trouve les articles privés et acceptés
     * @return array
     */
    public function findPrive(): array
    {
        return $this->findAll([
            'etat' => ArticleEntity::ETAT_ACCEPTE,
            'visibilite' => ArticleEntity::VISIBILITE_PRIVE
        ]);
    }

    /**
     * Trouve les articles récents
     * @param int $limit
     * @return array
     */
    public function findRecentArticles(int $limit = 10): array
    {
        $query = "SELECT * FROM Article WHERE etat = :etat ORDER BY date_creation DESC LIMIT " . (int) $limit;
        $params = ['etat' => ArticleEntity::ETAT_ACCEPTE];
        $this->logQuery($query, $params);

        $articlesData = $this->db->query($query, $params);
        return $this->createEntitiesFromDataArray($articlesData);
    }

    /**
     * Recherche d'articles par titre
     * @param string $titre
     * @return array
     */
    public function findByTitre(string $titre): array
    {
        $query = "SELECT * FROM Article WHERE titre LIKE :titre AND etat = :etat ORDER BY date_creation DESC";
        $params = [
            'titre' => "%$titre%",
            'etat' => ArticleEntity::ETAT_ACCEPTE
        ];
        $this->logQuery($query, $params);

        $articlesData = $this->db->query($query, $params);
        return $this->createEntitiesFromDataArray($articlesData);
    }

    /**
     * Change l'état d'un article
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
            ArticleEntity::ETAT_ACCEPTE,
            ArticleEntity::ETAT_EN_ATTENTE,
            ArticleEntity::ETAT_EFFACE,
            ArticleEntity::ETAT_REFUSE
        ];

        if (!in_array($nouvelEtat, $validEtats)) {
            throw new InvalidArgumentException("État invalide: $nouvelEtat");
        }

        if (!$this->findById($id)) {
            throw new RuntimeException("Article introuvable avec l'ID: $id");
        }

        $query = "UPDATE Article SET etat = :etat, date_modification = NOW() WHERE id = :id";
        $params = ['id' => $id, 'etat' => $nouvelEtat];
        $this->logQuery($query, $params);

        return $this->db->execute($query, $params) > 0;
    }

    /**
     * Compte le nombre d'articles d'un utilisateur
     * @param int $utilisateurId
     * @return int
     */
    public function countByUtilisateur(int $utilisateurId): int
    {
        $query = "SELECT COUNT(*) as count FROM Article WHERE utilisateur_id = :utilisateur_id";
        $params = ['utilisateur_id' => $utilisateurId];
        $this->logQuery($query, $params);

        $result = $this->db->queryOne($query, $params);
        return (int) $result['count'];
    }

    /**
     * Compte le nombre d'articles par état
     * @param string $etat
     * @return int
     */
    public function countByEtat(string $etat): int
    {
        $query = "SELECT COUNT(*) as count FROM Article WHERE etat = :etat";
        $params = ['etat' => $etat];
        $this->logQuery($query, $params);

        $result = $this->db->queryOne($query, $params);
        return (int) $result['count'];
    }

    /**
     * Trouve les articles avec informations d'auteur (jointure)
     * @param int $limit
     * @return array
     */
    public function findWithAuthorDetails(int $limit = 50): array
    {
        $query = "SELECT a.*, u.pseudonyme as auteur_pseudonyme 
                  FROM Article a 
                  LEFT JOIN Utilisateur u ON a.utilisateur_id = u.id 
                  WHERE a.etat = :etat 
                  ORDER BY a.date_creation DESC 
                  LIMIT " . (int) $limit;

        $params = ['etat' => ArticleEntity::ETAT_ACCEPTE];
        $this->logQuery($query, $params);

        return $this->db->query($query, $params);
    }

    /**
     * Insère un nouvel article
     * @param ArticleEntity $article
     * @return ArticleEntity
     */
    private function insert(ArticleEntity $article): ArticleEntity
    {
        $query = "INSERT INTO Article (titre, contenu, visibilite, etat, utilisateur_id, date_creation, date_modification) 
                  VALUES (:titre, :contenu, :visibilite, :etat, :utilisateur_id, NOW(), NOW())";

        $params = [
            'titre' => $article->getTitre(),
            'contenu' => $article->getContenu(),
            'visibilite' => $article->getVisibilite(),
            'etat' => $article->getEtat(),
            'utilisateur_id' => $article->getUtilisateurId()
        ];

        $this->logQuery($query, $params);
        $this->db->execute($query, $params);
        $article->setId($this->db->lastInsertId());

        return $article;
    }

    /**
     * Met à jour un article existant
     * @param ArticleEntity $article
     * @return ArticleEntity
     */
    private function update(ArticleEntity $article): ArticleEntity
    {
        $query = "UPDATE Article SET 
                    titre = :titre, 
                    contenu = :contenu, 
                    visibilite = :visibilite, 
                    etat = :etat, 
                    date_modification = NOW() 
                  WHERE id = :id";

        $params = [
            'id' => $article->getId(),
            'titre' => $article->getTitre(),
            'contenu' => $article->getContenu(),
            'visibilite' => $article->getVisibilite(),
            'etat' => $article->getEtat()
        ];

        $this->logQuery($query, $params);
        $this->db->execute($query, $params);

        return $article;
    }
}
