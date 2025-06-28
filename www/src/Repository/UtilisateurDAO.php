<?php
namespace App\Repository;

use App\Core\Database;
use App\Entity\UtilisateurEntity;
use InvalidArgumentException;
use RuntimeException;

class UtilisateurDAO
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Crée une entité Utilisateur à partir des données de la base
     * @param array $data
     * @return UtilisateurEntity
     */
    private function createEntityFromData(array $data): UtilisateurEntity
    {
        $entity = new UtilisateurEntity();
        return $entity->hydrate($data);
    }

    /**
     * Crée un tableau d'entités Utilisateur à partir d'un tableau de données
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
            error_log("UtilisateurDAO Query: $query | Params: " . json_encode($params));
        }
    }

    /**
     * Trouve un utilisateur par son ID
     * @param int $id
     * @return UtilisateurEntity|null
     */
    public function findById(int $id): ?UtilisateurEntity
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("ID invalide");
        }

        $query = "SELECT * FROM Utilisateur WHERE id = :id";
        $params = ['id' => $id];
        $this->logQuery($query, $params);

        $utilisateurData = $this->db->queryOne($query, $params);

        if (!$utilisateurData) {
            return null;
        }

        return $this->createEntityFromData($utilisateurData);
    }

    /**
     * Trouve tous les utilisateurs
     * @return array
     */
    public function findAll(): array
    {
        $query = "SELECT * FROM Utilisateur ORDER BY date_creation DESC";
        $this->logQuery($query);

        $utilisateursData = $this->db->query($query);
        return $this->createEntitiesFromDataArray($utilisateursData);
    }

    /**
     * Trouve les utilisateurs avec pagination
     * @param int $page
     * @param int $perPage
     * @param array $filtres
     * @return array
     */
    public function findAllPaginated(int $page = 1, int $perPage = 10, array $filtres = []): array
    {
        $offset = ($page - 1) * $perPage;

        $query = "SELECT * FROM Utilisateur";
        $params = [];
        $conditions = [];

        if (!empty($filtres['type_compte'])) {
            $conditions[] = "type_compte = :type_compte";
            $params['type_compte'] = $filtres['type_compte'];
        }

        if (isset($filtres['est_banni'])) {
            $conditions[] = "est_banni = :est_banni";
            $params['est_banni'] = (int) $filtres['est_banni'];
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " ORDER BY date_creation DESC LIMIT " . (int) $perPage . " OFFSET " . (int) $offset;
        $this->logQuery($query, $params);

        $utilisateursData = $this->db->query($query, $params);
        return $this->createEntitiesFromDataArray($utilisateursData);
    }

    /**
     * Enregistre un utilisateur (création ou mise à jour)
     * @param UtilisateurEntity $utilisateur
     * @return UtilisateurEntity
     */
    public function save(UtilisateurEntity $utilisateur): UtilisateurEntity
    {
        try {
            $this->db->beginTransaction();

            if ($utilisateur->getId()) {
                $result = $this->update($utilisateur);
            } else {
                $result = $this->insert($utilisateur);
            }

            $this->db->commit();
            return $result;

        } catch (\Exception $e) {
            $this->db->rollback();
            throw new RuntimeException("Erreur lors de la sauvegarde de l'utilisateur: " . $e->getMessage());
        }
    }

    /**
     * Trouve un utilisateur par son email
     * @param string $email
     * @return UtilisateurEntity|null
     */
    public function findByEmail(string $email): ?UtilisateurEntity
    {
        if (empty($email)) {
            throw new InvalidArgumentException("Email vide");
        }

        $query = "SELECT * FROM Utilisateur WHERE email = :email";
        $params = ['email' => $email];
        $this->logQuery($query, $params);

        $utilisateurData = $this->db->queryOne($query, $params);

        if (!$utilisateurData) {
            return null;
        }

        return $this->createEntityFromData($utilisateurData);
    }

    /**
     * Trouve un utilisateur par son pseudonyme
     * @param string $pseudonyme
     * @return UtilisateurEntity|null
     */
    public function findByPseudonyme(string $pseudonyme): ?UtilisateurEntity
    {
        if (empty($pseudonyme)) {
            throw new InvalidArgumentException("Pseudonyme vide");
        }

        $query = "SELECT * FROM Utilisateur WHERE pseudonyme = :pseudonyme";
        $params = ['pseudonyme' => $pseudonyme];
        $this->logQuery($query, $params);

        $utilisateurData = $this->db->queryOne($query, $params);

        if (!$utilisateurData) {
            return null;
        }

        return $this->createEntityFromData($utilisateurData);
    }

    /**
     * Trouve les comptes en attente de validation
     * @return array
     */
    public function findEnAttente(): array
    {
        $query = "SELECT * FROM Utilisateur WHERE etat_compte = 'en_attente' ORDER BY date_creation DESC";
        $this->logQuery($query);

        $utilisateursData = $this->db->query($query);
        return $this->createEntitiesFromDataArray($utilisateursData);
    }

    /**
     * Trouve les utilisateurs par type de compte
     * @param string $typeCompte
     * @return array
     */
    public function findByType(string $typeCompte): array
    {
        $validTypes = [
            UtilisateurEntity::TYPE_ADMINISTRATEUR,
            UtilisateurEntity::TYPE_MODERATEUR,
            UtilisateurEntity::TYPE_REDACTEUR
        ];

        if (!in_array($typeCompte, $validTypes)) {
            throw new InvalidArgumentException("Type de compte invalide: $typeCompte");
        }

        $query = "SELECT * FROM Utilisateur WHERE type_compte = :type_compte ORDER BY date_creation DESC";
        $params = ['type_compte' => $typeCompte];
        $this->logQuery($query, $params);

        $utilisateursData = $this->db->query($query, $params);
        return $this->createEntitiesFromDataArray($utilisateursData);
    }

    /**
     * Bannit ou réhabilite un utilisateur
     * @param int $id
     * @param bool $estBanni
     * @return bool
     */
    public function bannir(int $id, bool $estBanni = true): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("ID invalide");
        }

        if (!$this->findById($id)) {
            throw new RuntimeException("Utilisateur introuvable avec l'ID: $id");
        }

        $query = "UPDATE Utilisateur SET est_banni = :est_banni WHERE id = :id";
        $params = [
            'id' => $id,
            'est_banni' => (int) $estBanni
        ];
        $this->logQuery($query, $params);

        return $this->db->execute($query, $params) > 0;
    }

    /**
     * Change le type de compte d'un utilisateur
     * @param int $id
     * @param string $nouveauType
     * @return bool
     */
    public function changerTypeCompte(int $id, string $nouveauType): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("ID invalide");
        }

        $validTypes = [
            UtilisateurEntity::TYPE_ADMINISTRATEUR,
            UtilisateurEntity::TYPE_MODERATEUR,
            UtilisateurEntity::TYPE_REDACTEUR
        ];

        if (!in_array($nouveauType, $validTypes)) {
            throw new InvalidArgumentException("Type de compte invalide: $nouveauType");
        }

        if (!$this->findById($id)) {
            throw new RuntimeException("Utilisateur introuvable avec l'ID: $id");
        }

        $query = "UPDATE Utilisateur SET type_compte = :nouveau_type WHERE id = :id";
        $params = [
            'id' => $id,
            'nouveau_type' => $nouveauType
        ];
        $this->logQuery($query, $params);

        return $this->db->execute($query, $params) > 0;
    }

    /**
     * Valide un compte utilisateur
     * @param int $id
     * @return bool
     */
    public function validerCompte(int $id): bool
    {
        $query = "UPDATE Utilisateur SET etat_compte = 'valide' WHERE id = :id";
        $params = ['id' => $id];
        $this->logQuery($query, $params);

        return $this->db->execute($query, $params) > 0;
    }

    /**
     * Refuse un compte utilisateur
     * @param int $id
     * @return bool
     */
    public function refuserCompte(int $id): bool
    {
        $query = "UPDATE Utilisateur SET etat_compte = 'refuse' WHERE id = :id";
        $params = ['id' => $id];
        $this->logQuery($query, $params);

        return $this->db->execute($query, $params) > 0;
    }

    /**
     * Supprime un utilisateur (le marque comme banni)
     * @param int $id
     * @return bool
     */
    public function supprimerUtilisateur(int $id): bool
    {
        return $this->bannir($id, true);
    }

    /**
     * Vérifie si un email est unique
     * @param string $email
     * @param int|null $excludeId ID à exclure de la recherche (pour la mise à jour)
     * @return bool
     */
    public function isEmailUnique(string $email, ?int $excludeId = null): bool
    {
        if (empty($email)) {
            return false;
        }

        $query = "SELECT COUNT(*) as count FROM Utilisateur WHERE email = :email";
        $params = ['email' => $email];

        if ($excludeId !== null) {
            $query .= " AND id != :id";
            $params['id'] = $excludeId;
        }

        $this->logQuery($query, $params);
        $result = $this->db->queryOne($query, $params);
        return $result['count'] == 0;
    }

    /**
     * Vérifie si un pseudonyme est unique
     * @param string $pseudonyme
     * @param int|null $excludeId ID à exclure de la recherche (pour la mise à jour)
     * @return bool
     */
    public function isPseudonnymeUnique(string $pseudonyme, ?int $excludeId = null): bool
    {
        if (empty($pseudonyme)) {
            return false;
        }

        $query = "SELECT COUNT(*) as count FROM Utilisateur WHERE pseudonyme = :pseudonyme";
        $params = ['pseudonyme' => $pseudonyme];

        if ($excludeId !== null) {
            $query .= " AND id != :id";
            $params['id'] = $excludeId;
        }

        $this->logQuery($query, $params);
        $result = $this->db->queryOne($query, $params);
        return $result['count'] == 0;
    }

    /**
     * Compte le nombre total d'utilisateurs
     * @param array $filtres
     * @return int
     */
    public function countAll(array $filtres = []): int
    {
        $query = "SELECT COUNT(*) as count FROM Utilisateur";
        $params = [];
        $conditions = [];

        if (!empty($filtres['type_compte'])) {
            $conditions[] = "type_compte = :type_compte";
            $params['type_compte'] = $filtres['type_compte'];
        }

        if (isset($filtres['est_banni'])) {
            $conditions[] = "est_banni = :est_banni";
            $params['est_banni'] = (int) $filtres['est_banni'];
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $this->logQuery($query, $params);
        $result = $this->db->queryOne($query, $params);
        return (int) $result['count'];
    }

    /**
     * Trouve les utilisateurs actifs récents
     * @param int $limit
     * @return array
     */
    public function findRecentUsers(int $limit = 10): array
    {
        $query = "SELECT * FROM Utilisateur WHERE est_banni = 0 ORDER BY date_creation DESC LIMIT " . (int) $limit;
        $this->logQuery($query);

        $utilisateursData = $this->db->query($query);
        return $this->createEntitiesFromDataArray($utilisateursData);
    }

    /**
     * Trouve les utilisateurs avec statistiques (jointures)
     * @param int $limit
     * @return array
     */
    public function findWithStats(int $limit = 50): array
    {
        $query = "SELECT u.*, 
                         COUNT(DISTINCT a.id) as nb_articles,
                         COUNT(DISTINCT c.id) as nb_commentaires
                  FROM Utilisateur u
                  LEFT JOIN Article a ON u.id = a.utilisateur_id AND a.etat = 'accepte'
                  LEFT JOIN Commentaire c ON u.id = c.utilisateur_id AND c.etat = 'accepte'
                  GROUP BY u.id
                  ORDER BY u.date_creation DESC 
                  LIMIT " . (int) $limit;

        $this->logQuery($query);
        return $this->db->query($query);
    }

    /**
     * Insère un nouvel utilisateur
     * @param UtilisateurEntity $utilisateur
     * @return UtilisateurEntity
     */
    private function insert(UtilisateurEntity $utilisateur): UtilisateurEntity
    {
        $query = "INSERT INTO Utilisateur (email, mot_de_passe, pseudonyme, type_compte, etat_compte, est_banni, date_creation) 
                  VALUES (:email, :mot_de_passe, :pseudonyme, :type_compte, :etat_compte, :est_banni, NOW())";

        $params = [
            'email' => $utilisateur->getEmail(),
            'mot_de_passe' => $utilisateur->getMotDePasse(),
            'pseudonyme' => $utilisateur->getPseudonyme(),
            'type_compte' => $utilisateur->getTypeCompte(),
            'etat_compte' => $utilisateur->getEtatCompte(),
            'est_banni' => (int) $utilisateur->getEstBanni()
        ];

        $this->logQuery($query, $params);
        $this->db->execute($query, $params);
        $utilisateur->setId($this->db->lastInsertId());

        return $utilisateur;
    }

    /**
     * Met à jour un utilisateur existant
     * @param UtilisateurEntity $utilisateur
     * @return UtilisateurEntity
     */
    private function update(UtilisateurEntity $utilisateur): UtilisateurEntity
    {
        $query = "UPDATE Utilisateur SET 
                    email = :email, 
                    mot_de_passe = :mot_de_passe, 
                    pseudonyme = :pseudonyme, 
                    type_compte = :type_compte, 
                    etat_compte = :etat_compte,
                    est_banni = :est_banni 
                  WHERE id = :id";

        $params = [
            'id' => $utilisateur->getId(),
            'email' => $utilisateur->getEmail(),
            'mot_de_passe' => $utilisateur->getMotDePasse(),
            'pseudonyme' => $utilisateur->getPseudonyme(),
            'type_compte' => $utilisateur->getTypeCompte(),
            'etat_compte' => $utilisateur->getEtatCompte(),
            'est_banni' => (int) $utilisateur->getEstBanni()
        ];

        $this->logQuery($query, $params);
        $this->db->execute($query, $params);

        return $utilisateur;
    }
}
