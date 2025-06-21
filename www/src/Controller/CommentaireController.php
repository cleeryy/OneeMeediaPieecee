<?php
namespace App\Controller;

use App\Service\CommentaireService;

class CommentaireController extends BaseController
{
    protected CommentaireService $commentaireService; // ✅ PROTECTED

    public function __construct()
    {
        parent::__construct();
        $this->commentaireService = new CommentaireService();
    }

    /**
     * GET /api/article/{article_id}/commentaire - Commentaires d'un article
     */
    public function indexByArticle(int $articleId): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendError('Méthode non autorisée', 405);
                return;
            }

            $userId = null;
            // Authentification OPTIONNELLE pour voir les commentaires
            $token = $this->getBearerToken();
            if ($token) {
                $userId = $this->validateToken($token);
            }

            $commentaires = $this->commentaireService->getCommentairesArticle($articleId, $userId);

            // Formater pour l'API
            $commentairesData = array_map(function ($commentaire) {
                return [
                    'id' => $commentaire->getId(),
                    'contenu' => $commentaire->getContenu(),
                    'date_creation' => $commentaire->getDateCreation()->format('Y-m-d\TH:i:s'),
                    'etat' => $commentaire->getEtat(),
                    'utilisateur_id' => $commentaire->getUtilisateurId()
                ];
            }, $commentaires);

            $this->sendSuccess(['commentaires' => $commentairesData]);

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * POST /api/article/{article_id}/commentaire - Création de commentaire
     */
    public function create(int $articleId): void
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
            if (!$data || empty($data['contenu'])) {
                $this->sendError('Contenu du commentaire requis', 400);
                return;
            }

            $commentaire = $this->commentaireService->creerCommentaire(
                $data['contenu'],
                $articleId,
                $this->getCurrentUserId()
            );

            $this->sendSuccess([
                'id' => $commentaire->getId(),
                'contenu' => $commentaire->getContenu(),
                'etat' => $commentaire->getEtat()
            ], 'Commentaire créé avec succès', 201);

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * PUT /api/commentaire/{id} - Modification de commentaire
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
            if (!$data || empty($data['contenu'])) {
                $this->sendError('Contenu du commentaire requis', 400);
                return;
            }

            $commentaire = $this->commentaireService->modifierCommentaire(
                $id,
                $data['contenu'],
                $this->getCurrentUserId()
            );

            $this->sendSuccess([
                'id' => $commentaire->getId(),
                'contenu' => $commentaire->getContenu(),
                'etat' => $commentaire->getEtat()
            ], 'Commentaire modifié avec succès');

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * DELETE /api/commentaire/{id} - Suppression de commentaire
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

            $success = $this->commentaireService->supprimerCommentaire($id, $this->getCurrentUserId());

            if ($success) {
                $this->sendSuccess(null, 'Commentaire supprimé avec succès');
            } else {
                $this->sendError('Erreur lors de la suppression', 500);
            }

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
}
