<?php
namespace App\Controller;

use App\Service\UtilisateurService;

class UtilisateurController extends BaseController
{
    protected UtilisateurService $utilisateurService; // ✅ PROTECTED

    public function __construct()
    {
        parent::__construct();
        $this->utilisateurService = new UtilisateurService();
    }

    /**
     * POST /api/utilisateur - Création de compte
     */
    public function create(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->sendError('Méthode non autorisée', 405);
                return;
            }

            $data = $this->getJsonInput();
            if (!$data || empty($data['email']) || empty($data['password']) || empty($data['pseudonyme'])) {
                $this->sendError('Email, mot de passe et pseudonyme requis', 400);
                return;
            }

            $user = $this->utilisateurService->creerCompte(
                $data['email'],
                $data['password'],
                $data['pseudonyme']
            );

            $this->sendSuccess(
                ['id' => $user->getId()],
                'Compte créé avec succès, en attente de validation par un administrateur',
                201
            );

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/utilisateur/{id} - Récupération du profil
     */
    public function show(int $id): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendError('Méthode non autorisée', 405);
                return;
            }

            $user = $this->utilisateurService->getUtilisateurById($id);
            if (!$user) {
                $this->sendError('Utilisateur introuvable', 404);
                return;
            }

            // Données publiques uniquement
            $userData = [
                'id' => $user->getId(),
                'pseudonyme' => $user->getPseudonyme(),
                'date_creation' => $user->getDateCreation()->format('Y-m-d\TH:i:s'),
                'type_compte' => $user->getTypeCompte()
            ];

            // Si authentifié et c'est son propre profil, ajouter des infos privées
            $token = $this->getBearerToken();
            if ($token && $this->validateToken($token) === $id) {
                $userData['email'] = $user->getEmail();
                $userData['est_banni'] = $user->getEstBanni();
            }

            $this->sendSuccess($userData);

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * PUT /api/utilisateur/{id} - Modification du profil
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

            // Vérifier que c'est son propre profil ou qu'il est admin
            $currentUserId = $this->getCurrentUserId();
            $currentUser = $this->utilisateurService->getUtilisateurById($currentUserId);

            if ($currentUserId !== $id && !$currentUser->isAdministrateur()) {
                $this->sendError('Permissions insuffisantes', 403);
                return;
            }

            $data = $this->getJsonInput();
            if (!$data) {
                $this->sendError('Données JSON invalides', 400);
                return;
            }

            $user = $this->utilisateurService->mettreAJourProfil($id, $data);

            $this->sendSuccess([
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'pseudonyme' => $user->getPseudonyme()
            ], 'Profil mis à jour avec succès');

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * DELETE /api/utilisateur/{id} - Fermeture de compte
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

            // Vérifier que c'est son propre compte
            if ($this->getCurrentUserId() !== $id) {
                $this->sendError('Vous ne pouvez supprimer que votre propre compte', 403);
                return;
            }

            $data = $this->getJsonInput();
            $supprimerContenus = $data['supprimer_contenus'] ?? false;

            $success = $this->utilisateurService->fermerCompte($id, $supprimerContenus);

            if ($success) {
                // Supprimer les données de session
                unset($_SESSION['auth_token']);
                unset($_SESSION['user_id']);
                unset($_SESSION['current_user_id']);

                $this->sendSuccess(null, 'Compte fermé avec succès');
            } else {
                $this->sendError('Erreur lors de la fermeture du compte', 500);
            }

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
}
