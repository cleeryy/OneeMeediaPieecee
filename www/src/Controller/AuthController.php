<?php
namespace App\Controller;

use App\Service\UtilisateurService;

class AuthController extends BaseController
{
    protected UtilisateurService $utilisateurService; // ✅ PROTECTED

    public function __construct()
    {
        parent::__construct();
        $this->utilisateurService = new UtilisateurService();
    }

    /**
     * POST /api/auth/login
     */
    public function login(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->sendError('Méthode non autorisée', 405);
                return;
            }

            $data = $this->getJsonInput();
            if (!$data || empty($data['email']) || empty($data['password'])) {
                $this->sendError('Email et mot de passe requis', 400);
                return;
            }

            // Authentification
            $user = $this->utilisateurService->authentifier($data['email'], $data['password']);

            if (!$user) {
                $this->sendError('Identifiants invalides', 401);
                return;
            }

            // Générer un token simple
            $token = bin2hex(random_bytes(32));

            // Stocker dans la session
            $_SESSION['auth_token'] = $token;
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['current_user_id'] = $user->getId();

            $this->sendSuccess([
                'token' => $token,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'pseudonyme' => $user->getPseudonyme(),
                    'type_compte' => $user->getTypeCompte()
                ]
            ], 'Connexion réussie');

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->sendError('Méthode non autorisée', 405);
                return;
            }

            // Supprimer les données de session
            unset($_SESSION['auth_token']);
            unset($_SESSION['user_id']);
            unset($_SESSION['current_user_id']);

            $this->sendSuccess(null, 'Déconnexion réussie');

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * POST /api/auth/refresh
     */
    public function refresh(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->sendError('Méthode non autorisée', 405);
                return;
            }

            if (!$this->requireAuth()) {
                return;
            }

            // Générer un nouveau token
            $token = bin2hex(random_bytes(32));
            $_SESSION['auth_token'] = $token;

            $this->sendSuccess(['token' => $token], 'Token renouvelé');

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
}
