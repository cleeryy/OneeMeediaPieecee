<?php
namespace App\Service;

use App\Repository\UtilisateurDAO;
use App\Repository\ArticleDAO;
use App\Repository\CommentaireDAO;
use App\Repository\ModerationDAO;
use App\Entity\UtilisateurEntity;
use InvalidArgumentException;
use RuntimeException;

class UtilisateurService
{
    private UtilisateurDAO $utilisateurDAO;
    private ArticleDAO $articleDAO;
    private CommentaireDAO $commentaireDAO;
    private ModerationDAO $moderationDAO;

    public function __construct()
    {
        $this->utilisateurDAO = new UtilisateurDAO();
        $this->articleDAO = new ArticleDAO();
        $this->commentaireDAO = new CommentaireDAO();
        $this->moderationDAO = new ModerationDAO();
    }

    /**
     * Crée un nouveau compte utilisateur
     * @param string $email
     * @param string $motDePasse
     * @param string $pseudonyme
     * @return UtilisateurEntity
     */
    public function creerCompte(string $email, string $motDePasse, string $pseudonyme): UtilisateurEntity
    {
        // Validation des données
        $this->validerDonneesUtilisateur($email, $motDePasse, $pseudonyme);

        // Vérifier l'unicité de l'email et du pseudonyme
        if (!$this->utilisateurDAO->isEmailUnique($email)) {
            throw new InvalidArgumentException("Cet email est déjà utilisé");
        }

        if (!$this->utilisateurDAO->isPseudonnymeUnique($pseudonyme)) {
            throw new InvalidArgumentException("Ce pseudonyme est déjà utilisé");
        }

        // Créer l'utilisateur
        $utilisateur = new UtilisateurEntity();
        $utilisateur->setEmail($email)
            ->setMotDePasse(password_hash($motDePasse, PASSWORD_DEFAULT))
            ->setPseudonyme($pseudonyme);
        // Le type "rédacteur" et "non banni" sont définis par défaut dans le constructeur

        return $this->utilisateurDAO->save($utilisateur);
    }

    /**
     * Authentifie un utilisateur
     * @param string $email
     * @param string $motDePasse
     * @return UtilisateurEntity|null
     */
    public function authentifier(string $email, string $motDePasse): ?UtilisateurEntity
    {
        if (empty($email) || empty($motDePasse)) {
            return null;
        }

        $utilisateur = $this->utilisateurDAO->findByEmail($email);

        if (!$utilisateur) {
            return null;
        }

        // Vérifier si l'utilisateur est banni
        if ($utilisateur->getEstBanni()) {
            throw new RuntimeException("Votre compte a été suspendu");
        }

        // Vérifier le mot de passe
        if (!password_verify($motDePasse, $utilisateur->getMotDePasse())) {
            return null;
        }

        return $utilisateur;
    }

    /**
     * Valide un compte utilisateur (par un administrateur)
     * @param int $utilisateurId
     * @param int $administrateurId
     * @return bool
     */
    public function validerCompte(int $utilisateurId, int $administrateurId): bool
    {
        // Vérifier les permissions de l'administrateur
        $administrateur = $this->utilisateurDAO->findById($administrateurId);
        if (!$administrateur || !$administrateur->isAdministrateur()) {
            throw new RuntimeException("Permissions insuffisantes pour valider un compte");
        }

        // Valider le compte (débannir si nécessaire)
        return $this->utilisateurDAO->validerCompte($utilisateurId);
    }

    /**
     * Change le type de compte d'un utilisateur
     * @param int $utilisateurId
     * @param string $nouveauType
     * @param int $administrateurId
     * @return bool
     */
    public function changerTypeCompte(int $utilisateurId, string $nouveauType, int $administrateurId): bool
    {
        // Vérifier les permissions de l'administrateur
        $administrateur = $this->utilisateurDAO->findById($administrateurId);
        if (!$administrateur || !$administrateur->isAdministrateur()) {
            throw new RuntimeException("Seuls les administrateurs peuvent modifier les types de compte");
        }

        // Vérifier que l'utilisateur existe
        $utilisateur = $this->utilisateurDAO->findById($utilisateurId);
        if (!$utilisateur) {
            throw new RuntimeException("Utilisateur introuvable");
        }

        return $this->utilisateurDAO->changerTypeCompte($utilisateurId, $nouveauType);
    }

    /**
     * Signale un utilisateur
     * @param int $utilisateurSignaleId
     * @param int $moderateurId
     * @param string $description
     * @return bool
     */
    public function signalerUtilisateur(int $utilisateurSignaleId, int $moderateurId, string $description): bool
    {
        // Vérifier les permissions du modérateur
        $moderateur = $this->utilisateurDAO->findById($moderateurId);
        if (!$moderateur || (!$moderateur->isModerateur() && !$moderateur->isAdministrateur())) {
            throw new RuntimeException("Permissions insuffisantes pour signaler un utilisateur");
        }

        // Vérifier que l'utilisateur à signaler existe
        $utilisateurSignale = $this->utilisateurDAO->findById($utilisateurSignaleId);
        if (!$utilisateurSignale) {
            throw new RuntimeException("Utilisateur à signaler introuvable");
        }

        // Enregistrer le signalement dans la traçabilité
        $this->moderationDAO->enregistrerSignalement($utilisateurSignaleId, $moderateurId, $description);

        return true;
    }

    /**
     * Bannit un utilisateur
     * @param int $utilisateurId
     * @param int $administrateurId
     * @param string $description
     * @return bool
     */
    public function bannirUtilisateur(int $utilisateurId, int $administrateurId, string $description): bool
    {
        // Vérifier les permissions de l'administrateur
        $administrateur = $this->utilisateurDAO->findById($administrateurId);
        if (!$administrateur || !$administrateur->isAdministrateur()) {
            throw new RuntimeException("Seuls les administrateurs peuvent bannir des utilisateurs");
        }

        // Bannir l'utilisateur
        $success = $this->utilisateurDAO->bannir($utilisateurId, true);

        if ($success) {
            // Enregistrer l'action dans la traçabilité
            $this->moderationDAO->enregistrerSuppressionCompte($utilisateurId, $administrateurId, $description);
        }

        return $success;
    }

    /**
     * Ferme le compte d'un utilisateur et supprime ses contenus
     * @param int $utilisateurId
     * @param bool $supprimerContenus
     * @return bool
     */
    public function fermerCompte(int $utilisateurId, bool $supprimerContenus = false): bool
    {
        $utilisateur = $this->utilisateurDAO->findById($utilisateurId);
        if (!$utilisateur) {
            throw new RuntimeException("Utilisateur introuvable");
        }

        try {
            // Commencer une transaction (si votre classe Database le supporte)

            if ($supprimerContenus) {
                // Marquer tous les articles comme effacés
                $articles = $this->articleDAO->findByUtilisateur($utilisateurId);
                foreach ($articles as $article) {
                    $this->articleDAO->changerEtat($article->getId(), 'efface');
                }

                // Marquer tous les commentaires comme effacés
                $commentaires = $this->commentaireDAO->findByUtilisateur($utilisateurId);
                foreach ($commentaires as $commentaire) {
                    $this->commentaireDAO->changerEtat($commentaire->getId(), 'efface');
                }
            }

            // Bannir l'utilisateur (fermeture de compte = bannissement)
            $this->utilisateurDAO->bannir($utilisateurId, true);

            return true;

        } catch (\Exception $e) {
            // Rollback en cas d'erreur
            throw new RuntimeException("Erreur lors de la fermeture du compte: " . $e->getMessage());
        }
    }

    /**
     * Récupère un utilisateur par son ID
     * @param int $id
     * @return UtilisateurEntity|null
     */
    public function getUtilisateurById(int $id): ?UtilisateurEntity
    {
        return $this->utilisateurDAO->findById($id);
    }

    /**
     * Récupère tous les utilisateurs avec filtres
     * @param array $filtres
     * @return array
     */
    public function getUtilisateurs(array $filtres = []): array
    {
        if (!empty($filtres['type'])) {
            return $this->utilisateurDAO->findByType($filtres['type']);
        }

        return $this->utilisateurDAO->findAll();
    }

    /**
     * Met à jour le profil d'un utilisateur
     * @param int $utilisateurId
     * @param array $donnees
     * @return UtilisateurEntity
     */
    public function mettreAJourProfil(int $utilisateurId, array $donnees): UtilisateurEntity
    {
        $utilisateur = $this->utilisateurDAO->findById($utilisateurId);
        if (!$utilisateur) {
            throw new RuntimeException("Utilisateur introuvable");
        }

        // Validation et mise à jour des champs autorisés
        if (!empty($donnees['email'])) {
            if (!$this->utilisateurDAO->isEmailUnique($donnees['email'], $utilisateurId)) {
                throw new InvalidArgumentException("Cet email est déjà utilisé");
            }
            $utilisateur->setEmail($donnees['email']);
        }

        if (!empty($donnees['pseudonyme'])) {
            if (!$this->utilisateurDAO->isPseudonnymeUnique($donnees['pseudonyme'], $utilisateurId)) {
                throw new InvalidArgumentException("Ce pseudonyme est déjà utilisé");
            }
            $utilisateur->setPseudonyme($donnees['pseudonyme']);
        }

        if (!empty($donnees['motDePasse'])) {
            $this->validerMotDePasse($donnees['motDePasse']);
            $utilisateur->setMotDePasse(password_hash($donnees['motDePasse'], PASSWORD_DEFAULT));
        }

        return $this->utilisateurDAO->save($utilisateur);
    }

    /**
     * Valide les données d'un utilisateur
     * @param string $email
     * @param string $motDePasse
     * @param string $pseudonyme
     */
    private function validerDonneesUtilisateur(string $email, string $motDePasse, string $pseudonyme): void
    {
        // Validation email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Email invalide");
        }

        // Validation mot de passe
        $this->validerMotDePasse($motDePasse);

        // Validation pseudonyme
        if (empty($pseudonyme) || strlen($pseudonyme) < 3) {
            throw new InvalidArgumentException("Le pseudonyme doit contenir au moins 3 caractères");
        }

        if (strlen($pseudonyme) > 50) {
            throw new InvalidArgumentException("Le pseudonyme ne peut pas dépasser 50 caractères");
        }
    }

    /**
     * Valide un mot de passe
     * @param string $motDePasse
     */
    private function validerMotDePasse(string $motDePasse): void
    {
        if (empty($motDePasse) || strlen($motDePasse) < 6) {
            throw new InvalidArgumentException("Le mot de passe doit contenir au moins 6 caractères");
        }

        if (strlen($motDePasse) > 255) {
            throw new InvalidArgumentException("Le mot de passe est trop long");
        }
    }
}
