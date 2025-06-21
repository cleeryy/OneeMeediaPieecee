<?php
namespace App\Controller;

use App\Service\ArticleService;

class ArticleController extends BaseController
{
    protected ArticleService $articleService; // ✅ PROTECTED

    public function __construct()
    {
        parent::__construct();
        $this->articleService = new ArticleService();
    }

    /**
     * GET /api/article - Liste des articles
     */
    public function index(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendError('Méthode non autorisée', 405);
                return;
            }

            $userId = null;
            // Authentification OPTIONNELLE pour la liste des articles
            $token = $this->getBearerToken();
            if ($token) {
                $userId = $this->validateToken($token);
            }

            // Filtres optionnels
            $filtres = [];
            if (isset($_GET['visibilite'])) {
                $filtres['visibilite'] = $_GET['visibilite'];
            }
            if (isset($_GET['etat'])) {
                $filtres['etat'] = $_GET['etat'];
            }

            $articles = $this->articleService->getArticles($userId, $filtres);

            // Formater pour l'API
            $articlesData = array_map(function ($article) {
                return [
                    'id' => $article->getId(),
                    'titre' => $article->getTitre(),
                    'contenu' => substr($article->getContenu(), 0, 200) . '...', // Aperçu
                    'date_creation' => $article->getDateCreation()->format('Y-m-d\TH:i:s'),
                    'etat' => $article->getEtat(),
                    'visibilite' => $article->getVisibilite(),
                    'utilisateur_id' => $article->getUtilisateurId()
                ];
            }, $articles);

            $this->sendSuccess($articlesData);

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/article/{id} - Détail d'un article
     */
    public function show(int $id): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendError('Méthode non autorisée', 405);
                return;
            }

            $userId = null;
            // Authentification OPTIONNELLE pour voir un article
            $token = $this->getBearerToken();
            if ($token) {
                $userId = $this->validateToken($token);
            }

            $article = $this->articleService->getArticleById($id, $userId);
            if (!$article) {
                $this->sendError('Article introuvable', 404);
                return;
            }

            $articleData = [
                'id' => $article->getId(),
                'titre' => $article->getTitre(),
                'contenu' => $article->getContenu(),
                'date_creation' => $article->getDateCreation()->format('Y-m-d\TH:i:s'),
                'date_modification' => $article->getDateModification()->format('Y-m-d\TH:i:s'),
                'etat' => $article->getEtat(),
                'visibilite' => $article->getVisibilite(),
                'utilisateur_id' => $article->getUtilisateurId()
            ];

            $this->sendSuccess($articleData);

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * POST /api/article - Création d'article
     */
    public function create(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->sendError('Méthode non autorisée', 405);
                return;
            }

            if (!$this->requireAuth()) {
                return;
            }

            $data = $this->getJsonInput();
            if (!$data || empty($data['titre']) || empty($data['contenu'])) {
                $this->sendError('Titre et contenu requis', 400);
                return;
            }

            $article = $this->articleService->creerArticle(
                $data['titre'],
                $data['contenu'],
                $data['visibilite'] ?? 'public',
                $this->getCurrentUserId()
            );

            $this->sendSuccess([
                'id' => $article->getId(),
                'titre' => $article->getTitre(),
                'etat' => $article->getEtat()
            ], 'Article créé avec succès', 201);

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * PUT /api/article/{id} - Modification d'article
     */
    public function update(int $id): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                $this->sendError('Méthode non autorisée', 405);
                return;
            }

            if (!$this->requireAuth()) {
                return;
            }

            $data = $this->getJsonInput();
            if (!$data || empty($data['titre']) || empty($data['contenu'])) {
                $this->sendError('Titre et contenu requis', 400);
                return;
            }

            $article = $this->articleService->modifierArticle(
                $id,
                $data['titre'],
                $data['contenu'],
                $data['visibilite'] ?? 'public',
                $this->getCurrentUserId()
            );

            $this->sendSuccess([
                'id' => $article->getId(),
                'titre' => $article->getTitre(),
                'etat' => $article->getEtat()
            ], 'Article modifié avec succès');

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * DELETE /api/article/{id} - Suppression d'article
     */
    public function delete(int $id): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                $this->sendError('Méthode non autorisée', 405);
                return;
            }

            if (!$this->requireAuth()) {
                return;
            }

            $success = $this->articleService->supprimerArticle($id, $this->getCurrentUserId());

            if ($success) {
                $this->sendSuccess(null, 'Article supprimé avec succès');
            } else {
                $this->sendError('Erreur lors de la suppression', 500);
            }

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
}
