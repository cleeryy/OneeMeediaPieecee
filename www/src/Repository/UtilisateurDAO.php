<?php
namespace App\Repository;

use App\Core\Database;
use App\Entity\UtilisateurEntity;

class UtilisateurDAO
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // Méthodes CRUD de base
    public function findById(int $id): ?UtilisateurEntity
    {
        $query = "SELECT * FROM Utilisateur WHERE id = :id";
        $params = ['id' => $id];

        $utilisateur = $this->db->queryOne($query, $params);

        if (!$utilisateur) {
            return null;
        }

        $utilisateurEntity = new UtilisateurEntity();
        $utilisateurEntity->hydrate($utilisateur);

        return $utilisateurEntity;
    }
    public function findAll(): array
    {
        $query = "SELECT * FROM Utilisateur";
        $params = [];

        $query .= " ORDER BY id DESC";

        $utilisateurs = $this->db->query($query, $params);

        $utilisateursEntities = [];

        foreach ($utilisateurs as $utilisateur) {
            $utilisateurEntity = new UtilisateurEntity();
            $utilisateurEntity->hydrate($utilisateur);
            $utilisateursEntities[] = $utilisateurEntity;
        }

        return $utilisateursEntities;
    }
    public function save(UtilisateurEntity $utilisateur): UtilisateurEntity;

    // Méthodes spécifiques à la logique métier
    public function findByEmail(string $email): ?UtilisateurEntity
    {
        $query = "SELECT * FROM Utilisateur WHERE email = :email";
        $params = ['email' => $email];

        $utilisateur = $this->db->queryOne($query, $params);

        if (!$utilisateur) {
            return null;
        }

        $utilisateurEntity = new UtilisateurEntity();
        $utilisateurEntity->hydrate($utilisateur);

        return $utilisateurEntity;
    }
    public function findByPseudonyme(string $pseudonyme): ?UtilisateurEntity
    {
        $query = "SELECT * FROM Utilisateur WHERE pseudonyme = :pseudonyme";
        $params = ['pseudonyme' => $pseudonyme];

        $utilisateur = $this->db->queryOne($query, $params);

        if (!$utilisateur) {
            return null;
        }

        $utilisateurEntity = new UtilisateurEntity();
        $utilisateurEntity->hydrate($utilisateur);

        return $utilisateurEntity;
    }

    // TODO: IMPLEMENTER LA VALIDATION D'UN COMPTE
    // public function findEnAttente(): array // Comptes en attente de validation
    // {
    //     $query = "SELECT * FROM Utilisateur WHERE email = :email";
    //     $params = ['email' => $email];

    //     $utilisateur = $this->db->queryOne($query, $params);

    //     if (!$utilisateur) {
    //         return null;
    //     }

    //     $utilisateurEntity = new UtilisateurEntity();
    //     $utilisateurEntity->hydrate($utilisateur);

    //     return $utilisateurEntity;
    // }
    public function findByType(string $typeCompte): array
    {
        $query = "SELECT * FROM Utilisateur WHERE type_compte = :type_compte";
        $params = ['type_compte' => $typeCompte];

        $utilisateur = $this->db->queryOne($query, $params);

        if (!$utilisateur) {
            return null;
        }

        $utilisateurEntity = new UtilisateurEntity();
        $utilisateurEntity->hydrate($utilisateur);

        return $utilisateurEntity;
    }
    public function bannir(int $id, bool $estBanni = true): bool;
    public function changerTypeCompte(int $id, string $nouveauType): bool;
    public function validerCompte(int $id): bool;
    public function supprimerUtilisateur(int $id): bool;
    public function estEmailUnique(string $email): bool;
    public function estPseudonnymeUnique(string $pseudonyme): bool;
}
