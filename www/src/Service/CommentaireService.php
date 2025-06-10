<?php
namespace App\Service;

use App\Repository\CommentaireDAO;
use App\Repository\ArticleDAO;
use App\Repository\UtilisateurDAO;
use App\Repository\ModerationDAO;
use App\Entity\CommentaireEntity;
use App\Entity\ArticleEntity;
use InvalidArgumentException;
use RuntimeException;

class CommentaireService
{
    private CommentaireDAO $commentaireDAO;
    private ArticleDAO $articleDAO;
    private UtilisateurDAO $utilisateurDAO;
    private ModerationDAO $moderationDAO;

    public function __construct()
    {
        $this->commentaireDAO = new CommentaireDAO();
        $this->articleDAO = new ArticleDAO();
        $this->utilisateurDAO = new UtilisateurDAO();
        $this->moderationDAO = new ModerationDAO();
    }

    /**
     * Crée un nouveau commentaire
     * @param string $contenu
     * @param int $articleId
     * @param int $utilisateurId
     * @return CommentaireEntity
     */
    public function creerCommentaire(string $contenu, int $articleId, int $utilisateurId): CommentaireEntity
    {
        // Validation métier
        $this->validerContenuCommentaire($contenu);

        // Vérifier que l'article existe et est visible
        $article = $this->articleDAO->findById($articleId);
        if (!$article) {
            throw new RuntimeException("Article introuvable");
        }

        if ($article->getEtat() !== ArticleEntity::ETAT_ACCEPTE) {
            throw new RuntimeException("Impossible de commenter cet article");
        }

        // Vérifier que l'utilisateur existe et n'est pas banni
        $utilisateur = $this->utilisateurDAO->findById($utilisateurId);
        if (!$utilisateur || $utilisateur->getEstBanni()) {
            throw new RuntimeException("Utilisateur non autorisé à commenter");
        }

        // Créer le commentaire
        $commentaire = new CommentaireEntity();
        $commentaire->setContenu($contenu)
            ->setArticleId($articleId)
            ->setUtilisateurId($utilisateurId);

        // Règle métier : les modérateurs et admins publient directement
        if ($utilisateur->isModerateur() || $utilisateur->isAdministrateur()) {
            $commentaire->setEtat(CommentaireEntity::ETAT_ACCEPTE);
        }
        // Sinon l'état par défaut "en_attente" est déjà défini dans le constructeur

        return $this->commentaireDAO->save($commentaire);
    }

    /**
     * Modifie un commentaire existant
     * @param int $commentaireId
     * @param string $contenu
     * @param int $utilisateurId
     * @return CommentaireEntity
     */
    public function modifierCommentaire(int $commentaireId, string $contenu, int $utilisateurId): CommentaireEntity
    {
        // Validation métier
        $this->validerContenuCommentaire($contenu);

        // Récupérer le commentaire
        $commentaire = $this->commentaireDAO->findById($commentaireId);
        if (!$commentaire) {
            throw new RuntimeException("Commentaire introuvable");
        }

        // Vérifier les permissions (propriétaire ou admin/modérateur)
        $utilisateur = $this->utilisateurDAO->findById($utilisateurId);
        if (!$utilisateur || $utilisateur->getEstBanni()) {
            throw new RuntimeException("Utilisateur non autorisé");
        }

        if (
            $commentaire->getUtilisateurId() !== $utilisateurId &&
            !$utilisateur->isModerateur() &&
            !$utilisateur->isAdministrateur()
        ) {
            throw new RuntimeException("Vous n'êtes pas autorisé à modifier ce commentaire");
        }

        // Mettre à jour le commentaire
        $commentaire->setContenu($contenu);

        // Règle métier : dès qu'un commentaire est modifié, il repasse en modération
        // SAUF si c'est un modérateur/admin qui modifie
        if (!$utilisateur->isModerateur() && !$utilisateur->isAdministrateur()) {
            $commentaire->setEtat(CommentaireEntity::ETAT_EN_ATTENTE);
        }

        $commentaire->updateModification();

        return $this->commentaireDAO->save($commentaire);
    }

    /**
     * Modère un commentaire (accepte, refuse ou efface)
     * @param int $commentaireId
     * @param string $action
     * @param int $moderateurId
     * @param string|null $description
     * @return bool
     */
    public function modererCommentaire(int $commentaireId, string $action, int $moderateurId, ?string $description = null): bool
    {
        // Vérifications des permissions
        $moderateur = $this->utilisateurDAO->findById($moderateurId);
        if (!$moderateur || (!$moderateur->isModerateur() && !$moderateur->isAdministrateur())) {
            throw new RuntimeException("Permissions insuffisantes pour modérer");
        }

        $commentaire = $this->commentaireDAO->findById($commentaireId);
        if (!$commentaire) {
            throw new RuntimeException("Commentaire introuvable");
        }

        // Appliquer l'action de modération
        switch ($action) {
            case 'accepter':
                $this->commentaireDAO->changerEtat($commentaireId, CommentaireEntity::ETAT_ACCEPTE);
                break;

            case 'refuser':
                if (empty($description)) {
                    throw new InvalidArgumentException("Une description est requise pour refuser un commentaire");
                }
                $this->commentaireDAO->changerEtat($commentaireId, CommentaireEntity::ETAT_REFUSE);
                // Traçabilité (bonus)
                $this->moderationDAO->enregistrerRefusCommentaire($commentaireId, $moderateurId, $description);
                break;

            case 'effacer':
                $this->commentaireDAO->changerEtat($commentaireId, CommentaireEntity::ETAT_EFFACE);
                break;

            default:
                throw new InvalidArgumentException("Action de modération invalide");
        }

        return true;
    }

    /**
     * Récupère un commentaire par son ID
     * @param int $id
     * @param int|null $utilisateurId
     * @return CommentaireEntity|null
     */
    public function getCommentaireById(int $id, ?int $utilisateurId = null): ?CommentaireEntity
    {
        $commentaire = $this->commentaireDAO->findById($id);

        if (!$commentaire) {
            return null;
        }

        // Vérifier les permissions de lecture
        if (!$this->peutVoirCommentaire($commentaire, $utilisateurId)) {
            return null;
        }

        return $commentaire;
    }

    /**
     * Récupère tous les commentaires d'un article
     * @param int $articleId
     * @param int|null $utilisateurId
     * @return array
     */
    public function getCommentairesArticle(int $articleId, ?int $utilisateurId = null): array
    {
        // Vérifier que l'article existe
        $article = $this->articleDAO->findById($articleId);
        if (!$article) {
            throw new RuntimeException("Article introuvable");
        }

        $commentaires = $this->commentaireDAO->findByArticle($articleId);

        // Filtrer selon les permissions de lecture
        return array_filter($commentaires, function ($commentaire) use ($utilisateurId) {
            return $this->peutVoirCommentaire($commentaire, $utilisateurId);
        });
    }

    /**
     * Récupère les commentaires en attente de modération
     * @param int $moderateurId
     * @return array
     */
    public function getCommentairesEnAttente(int $moderateurId): array
    {
        // Vérifier les permissions
        $moderateur = $this->utilisateurDAO->findById($moderateurId);
        if (!$moderateur || (!$moderateur->isModerateur() && !$moderateur->isAdministrateur())) {
            throw new RuntimeException("Permissions insuffisantes");
        }

        return $this->commentaireDAO->findEnAttente();
    }

    /**
     * Récupère les commentaires d'un utilisateur
     * @param int $utilisateurId
     * @param int|null $demandeurId
     * @return array
     */
    public function getCommentairesUtilisateur(int $utilisateurId, ?int $demandeurId = null): array
    {
        $commentaires = $this->commentaireDAO->findByUtilisateur($utilisateurId);

        // Si c'est l'auteur lui-même, il voit tous ses commentaires
        if ($demandeurId === $utilisateurId) {
            return $commentaires;
        }

        // Sinon, filtrer selon les permissions
        return array_filter($commentaires, function ($commentaire) use ($demandeurId) {
            return $this->peutVoirCommentaire($commentaire, $demandeurId);
        });
    }

    /**
     * Supprime un commentaire (soft delete)
     * @param int $commentaireId
     * @param int $utilisateurId
     * @return bool
     */
    public function supprimerCommentaire(int $commentaireId, int $utilisateurId): bool
    {
        $commentaire = $this->commentaireDAO->findById($commentaireId);
        if (!$commentaire) {
            throw new RuntimeException("Commentaire introuvable");
        }

        // Vérifier les permissions
        $utilisateur = $this->utilisateurDAO->findById($utilisateurId);
        if (!$utilisateur) {
            throw new RuntimeException("Utilisateur introuvable");
        }

        // Seuls l'auteur, les modérateurs et admins peuvent supprimer
        if (
            $commentaire->getUtilisateurId() !== $utilisateurId &&
            !$utilisateur->isModerateur() &&
            !$utilisateur->isAdministrateur()
        ) {
            throw new RuntimeException("Permissions insuffisantes pour supprimer ce commentaire");
        }

        return $this->commentaireDAO->delete($commentaireId);
    }

    /**
     * Compte les commentaires d'un article
     * @param int $articleId
     * @return int
     */
    public function compterCommentairesArticle(int $articleId): int
    {
        return $this->commentaireDAO->countByArticle($articleId);
    }

    /**
     * Récupère les commentaires récents
     * @param int $limit
     * @param int|null $utilisateurId
     * @return array
     */
    public function getCommentairesRecents(int $limit = 10, ?int $utilisateurId = null): array
    {
        $commentaires = $this->commentaireDAO->findRecentCommentaires($limit);

        // Filtrer selon les permissions
        return array_filter($commentaires, function ($commentaire) use ($utilisateurId) {
            return $this->peutVoirCommentaire($commentaire, $utilisateurId);
        });
    }

    /**
     * Vérifie si un utilisateur peut voir un commentaire
     * @param CommentaireEntity $commentaire
     * @param int|null $utilisateurId
     * @return bool
     */
    private function peutVoirCommentaire(CommentaireEntity $commentaire, ?int $utilisateurId = null): bool
    {
        // Le commentaire doit être accepté pour être visible (sauf pour l'auteur)
        if ($commentaire->getEtat() !== CommentaireEntity::ETAT_ACCEPTE) {
            // L'auteur peut voir ses propres commentaires même non acceptés
            if ($utilisateurId && $commentaire->getUtilisateurId() === $utilisateurId) {
                return true;
            }

            // Les modérateurs et admins peuvent voir tous les commentaires
            if ($utilisateurId) {
                $utilisateur = $this->utilisateurDAO->findById($utilisateurId);
                if ($utilisateur && ($utilisateur->isModerateur() || $utilisateur->isAdministrateur())) {
                    return true;
                }
            }

            return false;
        }

        // Vérifier si l'article associé est visible
        $article = $this->articleDAO->findById($commentaire->getArticleId());
        if (!$article || $article->getEtat() !== ArticleEntity::ETAT_ACCEPTE) {
            return false;
        }

        // Si l'article est privé, vérifier que l'utilisateur est connecté
        if ($article->getVisibilite() === ArticleEntity::VISIBILITE_PRIVE) {
            return $utilisateurId !== null;
        }

        return true;
    }

    /**
     * Valide le contenu d'un commentaire
     * @param string $contenu
     */
    private function validerContenuCommentaire(string $contenu): void
    {
        if (empty(trim($contenu))) {
            throw new InvalidArgumentException("Le contenu du commentaire est requis");
        }

        if (strlen($contenu) < 3) {
            throw new InvalidArgumentException("Le commentaire doit contenir au moins 3 caractères");
        }

        if (strlen($contenu) > 10000) {
            throw new InvalidArgumentException("Le commentaire est trop long (maximum 10000 caractères)");
        }
    }
}
