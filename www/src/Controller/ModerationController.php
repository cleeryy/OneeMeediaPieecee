<?php
namespace App\Controller;

use App\Service\ArticleService;
use App\Service\CommentaireService;
use App\Service\UtilisateurService;
use App\Service\ModerationService;

class ModerationController extends BaseController
{
    protected ArticleService $articleService; // ✅ PROTECTED
    protected CommentaireService $commentaireService; // ✅ PROTECTED
    protected UtilisateurService $utilisateurService; // ✅ PROTECTED
    protected ModerationService $moderationService; // ✅ PROTECTED

    public function __construct()
    {
        parent::__construct();
        $this->articleService = new ArticleService();
        $this->commentaireService = new CommentaireService();
        $this->utilisateurService = new UtilisateurService();
        $this->moderationService = new ModerationService();
    }

    /**
     * GET /api/moderation/articles - Articles en attente
     */
    public function getArticlesEnAttente(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendError('Méthode non autorisée', 405);
                return;
            }

            if (!$this->requireRole('moderator')) {
                return;
            }

            $articles = $this->articleService->getArticlesEnAttente($this->getCurrentUserId());

            // Formater pour l'API
            $articlesData = array_map(function ($article) {
                return [
                    'id' => $article->getId(),
                    'titre' => $article->getTitre(),
                    'contenu' => substr($article->getContenu(), 0, 200) . '...',
                    'date_creation' => $article->getDateCreation()->format('Y-m-d\TH:i:s'),
                    'utilisateur_id' => $article->getUtilisateurId()
                ];
            }, $articles);

            $this->sendSuccess($articlesData);

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * PUT /api/moderation/article/{id} - Modérer un article
     */
    public function modererArticle(int $id): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                $this->sendError('Méthode non autorisée', 405);
                return;
            }

            if (!$this->requireRole('moderator')) {
                return;
            }

            $data = $this->getJsonInput();
            if (!$data || empty($data['action'])) {
                $this->sendError('Action de modération requise', 400);
                return;
            }

            $action = $data['action'];
            $description = $data['description'] ?? null;

            $success = $this->articleService->modererArticle(
                $id,
                $action,
                $this->getCurrentUserId(),
                $description
            );

            if ($success) {
                $this->sendSuccess(null, 'Article modéré avec succès');
            } else {
                $this->sendError('Erreur lors de la modération', 500);
            }

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/moderation/commentaires - Commentaires en attente
     */
    public function getCommentairesEnAttente(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendError('Méthode non autorisée', 405);
                return;
            }

            if (!$this->requireRole('moderator')) {
                return;
            }

            $commentaires = $this->commentaireService->getCommentairesEnAttente($this->getCurrentUserId());

            // Formater pour l'API
            $commentairesData = array_map(function ($commentaire) {
                return [
                    'id' => $commentaire->getId(),
                    'contenu' => $commentaire->getContenu(),
                    'date_creation' => $commentaire->getDateCreation()->format('Y-m-d\TH:i:s'),
                    'utilisateur_id' => $commentaire->getUtilisateurId(),
                    'article_id' => $commentaire->getArticleId()
                ];
            }, $commentaires);

            $this->sendSuccess($commentairesData);

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * PUT /api/moderation/commentaire/{id} - Modérer un commentaire
     */
    public function modererCommentaire(int $id): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                $this->sendError('Méthode non autorisée', 405);
                return;
            }

            if (!$this->requireRole('moderator')) {
                return;
            }

            $data = $this->getJsonInput();
            if (!$data || empty($data['action'])) {
                $this->sendError('Action de modération requise', 400);
                return;
            }

            $action = $data['action'];
            $description = $data['description'] ?? null;

            $success = $this->commentaireService->modererCommentaire(
                $id,
                $action,
                $this->getCurrentUserId(),
                $description
            );

            if ($success) {
                $this->sendSuccess(null, 'Commentaire modéré avec succès');
            } else {
                $this->sendError('Erreur lors de la modération', 500);
            }

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * POST /api/moderation/utilisateur/{id}/signaler - Signaler un utilisateur
     */
    public function signalerUtilisateur(int $id): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->sendError('Méthode non autorisée', 405);
                return;
            }

            if (!$this->requireRole('moderator')) {
                return;
            }

            $data = $this->getJsonInput();
            if (!$data || empty($data['description'])) {
                $this->sendError('Description du signalement requise', 400);
                return;
            }

            $success = $this->utilisateurService->signalerUtilisateur(
                $id,
                $this->getCurrentUserId(),
                $data['description']
            );

            if ($success) {
                $this->sendSuccess(null, 'Utilisateur signalé avec succès');
            } else {
                $this->sendError('Erreur lors du signalement', 500);
            }

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * PUT /api/moderation/utilisateur/{id}/bannir - Bannir un utilisateur (admin uniquement)
     */
    public function bannirUtilisateur(int $id): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                $this->sendError('Méthode non autorisée', 405);
                return;
            }

            if (!$this->requireRole('admin')) {
                return;
            }

            $data = $this->getJsonInput();
            if (!$data || empty($data['description'])) {
                $this->sendError('Description du bannissement requise', 400);
                return;
            }

            $success = $this->utilisateurService->bannirUtilisateur(
                $id,
                $this->getCurrentUserId(),
                $data['description']
            );

            if ($success) {
                $this->sendSuccess(null, 'Utilisateur banni avec succès');
            } else {
                $this->sendError('Erreur lors du bannissement', 500);
            }

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
}
