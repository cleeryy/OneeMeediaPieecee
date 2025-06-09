<?php
namespace App\Core;

use PDO;
use PDOException;


class Database
{
    private static $instance = null;
    private $pdo;

    /**
     * Constructeur privé pour empêcher l'instanciation directe (pattern Singleton)
     */
    private function __construct()
    {
        $config = require_once __DIR__ . '/../../config/database.php';

        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";

            $this->pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options']
            );
        } catch (PDOException $e) {
            // TODO: En prod plutot logger qu'afficher
            die('Erreur de connexion à la base données: ' . $e->getMessage());
        }
    }

    /**
     * Méthode pour obtenir l'instance unique de la classe Database
     * @return Database L'instance unique
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Prépare une requête SQL
     * @param string $query La requête SQL à préparer
     * @return \PDOStatement L'objet PDOStatement préparé
     */
    public function prepare($query)
    {
        return $this->pdo->prepare($query);
    }

    /**
     * Exécute une requête et retourne tous les résultats
     * @param string $query La requête SQL
     * @param array $params Les paramètres à lier
     * @return array Les résultats de la requête
     */
    public function query($query, $params = [])
    {
        $stmt = $this->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Exécute une requête et retourne un seul résultat
     * @param string $query La requête SQL
     * @param array $params Les paramètres à lier
     * @return array|false Le résultat de la requête ou false si pas de résultat
     */
    public function queryOne($query, $params = [])
    {
        $stmt = $this->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    /**
     * Exécute une requête d'insertion, de mise à jour ou de suppression
     * @param string $query La requête SQL
     * @param array $params Les paramètres à lier
     * @return int Le nombre de lignes affectées
     */
    public function execute($query, $params = [])
    {
        $stmt = $this->prepare($query);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Retourne l'ID de la dernière insertion
     * @return string L'ID de la dernière ligne insérée
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Débute une transaction
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Valide une transaction
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     * Annule une transaction
     */
    public function rollback()
    {
        return $this->pdo->rollBack();
    }
}

?>