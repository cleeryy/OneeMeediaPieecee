<?php
namespace App\Service;

use App\Repository\ArticleDAO;
use App\Repository\UtilisateurDAO;
use App\Repository\ModerationDAO;
use App\Entity\ArticleEntity;
use App\Entity\UtilisateurEntity;
use InvalidArgumentException;
use RuntimeException;

class ArticleService
{
    private ArticleDAO $articleDAO;
    private UtilisateurDAO $utilisateurDAO;
    private ModerationDAO $moderationDAO;

    public function __construct()
    {
        $this->articleDAO = new ArticleDAO();
        $this->utilisateurDAO = new UtilisateurDAO();
        $this->moderationDAO = new ModerationDAO();
    }

    /**
     * Crée un nouvel article
     * @param string $titre
     * @param string $contenu
     * @param string $visibilite
     * @param int $utilisateurId
     * @return ArticleEntity
     */
    public function creerArticle(string $titre, string $contenu, string $visibilite, int $utilisateurId): ArticleEntity
    {
        // Validation métier
        $this->validerDonneesArticle($titre, $contenu, $visibilite);

        // Vérifier que l'utilisateur existe et n'est pas banni
        $utilisateur = $this->utilisateurDAO->findById($utilisateurId);
        if (!$utilisateur || $utilisateur->getEstBanni()) {
            throw new RuntimeException("Utilisateur non autorisé à créer un article");
        }

        // Créer l'article
        $article = new ArticleEntity();
        $article->setTitre($titre)
            ->setContenu($contenu)
            ->setVisibilite($visibilite)
            ->setUtilisateurId($utilisateurId);

        // Règle métier : les modérateurs et admins publient directement
        if ($utilisateur->isModerateur() || $utilisateur->isAdministrateur()) {
            $article->setEtat(ArticleEntity::ETAT_ACCEPTE);
        }
        // Sinon l'état par défaut "en_attente" est déjà défini dans le constructeur

        return $this->articleDAO->save($article);
    }

    /**
     * Modifie un article existant
     * @param int $articleId
     * @param string $titre
     * @param string $contenu
     * @param string $visibilite
     * @param int $utilisateurId
     * @return ArticleEntity
     */
    public function modifierArticle(int $articleId, string $titre, string $contenu, string $visibilite, int $utilisateurId): ArticleEntity
    {
        // Validation métier
        $this->validerDonneesArticle($titre, $contenu, $visibilite);

        // Récupérer l'article
        $article = $this->articleDAO->findById($articleId);
        if (!$article) {
            throw new RuntimeException("Article introuvable");
        }

        // Vérifier les permissions (propriétaire ou admin/modérateur)
        $utilisateur = $this->utilisateurDAO->findById($utilisateurId);
        if (!$utilisateur || $utilisateur->getEstBanni()) {
            throw new RuntimeException("Utilisateur non autorisé");
        }

        // CORRECTION : Vérification stricte des permissions
        $estProprietaire = ($article->getUtilisateurId() === $utilisateurId);
        $estModerateurOuAdmin = ($utilisateur->isModerateur() || $utilisateur->isAdministrateur());

        if (!$estProprietaire && !$estModerateurOuAdmin) {
            throw new RuntimeException("Vous n'êtes pas autorisé à modifier cet article");
        }

        // Mettre à jour l'article
        if (!$utilisateur->isModerateur() && !$utilisateur->isAdministrateur()) {
            // Pour les rédacteurs : les setters vont automatiquement remettre en modération
            $article->setTitre($titre)
                ->setContenu($contenu)
                ->setVisibilite($visibilite);
        } else {
            // Pour les modérateurs/admins : éviter la remise en modération
            // On doit contourner les setters ou les modifier
            $etatActuel = $article->getEtat();

            $article->setTitre($titre)
                ->setContenu($contenu)
                ->setVisibilite($visibilite);

            // Remettre l'état précédent pour les modérateurs/admins
            $article->setEtat($etatActuel);
        }

        return $this->articleDAO->save($article);
    }

    /**
     * Modère un article (accepte, refuse ou efface)
     * @param int $articleId
     * @param string $action
     * @param int $moderateurId
     * @param string|null $description
     * @return bool
     */
    public function modererArticle(int $articleId, string $action, int $moderateurId, ?string $description = null): bool
    {
        // Vérifications des permissions
        $moderateur = $this->utilisateurDAO->findById($moderateurId);
        if (!$moderateur || (!$moderateur->isModerateur() && !$moderateur->isAdministrateur())) {
            throw new RuntimeException("Permissions insuffisantes pour modérer");
        }

        $article = $this->articleDAO->findById($articleId);
        if (!$article) {
            throw new RuntimeException("Article introuvable");
        }

        // Appliquer l'action de modération
        switch ($action) {
            case 'accepter':
                $this->articleDAO->changerEtat($articleId, ArticleEntity::ETAT_ACCEPTE);
                break;

            case 'refuser':
                if (empty($description)) {
                    throw new InvalidArgumentException("Une description est requise pour refuser un article");
                }
                $this->articleDAO->changerEtat($articleId, ArticleEntity::ETAT_REFUSE);
                // Traçabilité (bonus)
                $this->moderationDAO->enregistrerRefusArticle($articleId, $moderateurId, $description);
                break;

            case 'effacer':
                $this->articleDAO->changerEtat($articleId, ArticleEntity::ETAT_EFFACE);
                break;

            default:
                throw new InvalidArgumentException("Action de modération invalide");
        }

        return true;
    }

    /**
     * Récupère un article par son ID
     * @param int $id
     * @param int|null $utilisateurId
     * @return ArticleEntity|null
     */
    public function getArticleById(int $id, ?int $utilisateurId = null): ?ArticleEntity
    {
        $article = $this->articleDAO->findById($id);

        if (!$article) {
            return null;
        }

        // Vérifier les permissions de lecture
        if (!$this->peutVoirArticle($article, $utilisateurId)) {
            return null;
        }

        return $article;
    }

    /**
     * Récupère tous les articles visibles par un utilisateur
     * @param int|null $utilisateurId
     * @param array $filtres
     * @return array
     */
    public function getArticles(?int $utilisateurId = null, array $filtres = []): array
    {
        $articles = $this->articleDAO->findAll($filtres);

        // Filtrer selon les permissions de lecture
        return array_filter($articles, function ($article) use ($utilisateurId) {
            return $this->peutVoirArticle($article, $utilisateurId);
        });
    }

    /**
     * Récupère les articles en attente de modération
     * @param int $moderateurId
     * @return array
     */
    public function getArticlesEnAttente(int $moderateurId): array
    {
        // Vérifier les permissions
        $moderateur = $this->utilisateurDAO->findById($moderateurId);
        if (!$moderateur || (!$moderateur->isModerateur() && !$moderateur->isAdministrateur())) {
            throw new RuntimeException("Permissions insuffisantes");
        }

        return $this->articleDAO->findEnAttente();
    }

    /**
     * Récupère les articles d'un utilisateur
     * @param int $utilisateurId
     * @param int|null $demandeurId
     * @return array
     */
    public function getArticlesUtilisateur(int $utilisateurId, ?int $demandeurId = null): array
    {
        $articles = $this->articleDAO->findByUtilisateur($utilisateurId);

        // Si c'est l'auteur lui-même, il voit tous ses articles
        if ($demandeurId === $utilisateurId) {
            return $articles;
        }

        // Sinon, filtrer selon les permissions
        return array_filter($articles, function ($article) use ($demandeurId) {
            return $this->peutVoirArticle($article, $demandeurId);
        });
    }

    /**
     * Supprime un article (soft delete)
     * @param int $articleId
     * @param int $utilisateurId
     * @return bool
     */
    public function supprimerArticle(int $articleId, int $utilisateurId): bool
    {
        $article = $this->articleDAO->findById($articleId);
        if (!$article) {
            throw new RuntimeException("Article introuvable");
        }

        // Vérifier les permissions
        $utilisateur = $this->utilisateurDAO->findById($utilisateurId);
        if (!$utilisateur) {
            throw new RuntimeException("Utilisateur introuvable");
        }

        // Seuls l'auteur, les modérateurs et admins peuvent supprimer
        if (
            $article->getUtilisateurId() !== $utilisateurId &&
            !$utilisateur->isModerateur() &&
            !$utilisateur->isAdministrateur()
        ) {
            throw new RuntimeException("Permissions insuffisantes pour supprimer cet article");
        }

        return $this->articleDAO->delete($articleId);
    }

    /**
     * Recherche d'articles par titre
     * @param string $terme
     * @param int|null $utilisateurId
     * @return array
     */
    public function rechercherArticles(string $terme, ?int $utilisateurId = null): array
    {
        if (strlen($terme) < 3) {
            throw new InvalidArgumentException("Le terme de recherche doit contenir au moins 3 caractères");
        }

        $articles = $this->articleDAO->findByTitre($terme);

        // Filtrer selon les permissions
        return array_filter($articles, function ($article) use ($utilisateurId) {
            return $this->peutVoirArticle($article, $utilisateurId);
        });
    }

    /**
     * Vérifie si un utilisateur peut voir un article
     * @param ArticleEntity $article
     * @param int|null $utilisateurId
     * @return bool
     */
    private function peutVoirArticle(ArticleEntity $article, ?int $utilisateurId = null): bool
    {
        // L'article doit être accepté pour être visible (sauf pour l'auteur)
        if ($article->getEtat() !== ArticleEntity::ETAT_ACCEPTE) {
            // L'auteur peut voir ses propres articles même non acceptés
            if ($utilisateurId && $article->getUtilisateurId() === $utilisateurId) {
                return true;
            }

            // Les modérateurs et admins peuvent voir tous les articles
            if ($utilisateurId) {
                $utilisateur = $this->utilisateurDAO->findById($utilisateurId);
                if ($utilisateur && ($utilisateur->isModerateur() || $utilisateur->isAdministrateur())) {
                    return true;
                }
            }

            return false;
        }

        // Articles publics : visibles par tous
        if ($article->getVisibilite() === ArticleEntity::VISIBILITE_PUBLIC) {
            return true;
        }

        // Articles privés : visibles seulement par les utilisateurs connectés
        if ($article->getVisibilite() === ArticleEntity::VISIBILITE_PRIVE) {
            return $utilisateurId !== null;
        }

        return false;
    }

    /**
     * Valide les données d'un article
     * @param string $titre
     * @param string $contenu
     * @param string $visibilite
     */
    private function validerDonneesArticle(string $titre, string $contenu, string $visibilite): void
    {
        if (empty(trim($titre))) {
            throw new InvalidArgumentException("Le titre de l'article est requis");
        }

        if (strlen($titre) > 255) {
            throw new InvalidArgumentException("Le titre ne peut pas dépasser 255 caractères");
        }

        if (empty(trim($contenu))) {
            throw new InvalidArgumentException("Le contenu de l'article est requis");
        }

        if (!in_array($visibilite, [ArticleEntity::VISIBILITE_PUBLIC, ArticleEntity::VISIBILITE_PRIVE])) {
            throw new InvalidArgumentException("Visibilité invalide");
        }
    }
}
