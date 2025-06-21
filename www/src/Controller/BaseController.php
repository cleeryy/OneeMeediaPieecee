<?php
namespace App\Controller;

use App\Service\UtilisateurService;
use InvalidArgumentException;
use RuntimeException;

abstract class BaseController
{
    protected UtilisateurService $utilisateurService; // ✅ PROTECTED au lieu de private

    public function __construct()
    {
        $this->utilisateurService = new UtilisateurService();

        // Démarrer la session si elle n'est pas déjà démarrée
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Headers CORS et content-type
        $this->setCorsHeaders();
        header('Content-Type: application/json; charset=utf-8');
    }

    /**
     * Configure les headers CORS
     */
    protected function setCorsHeaders(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        // Gérer les requêtes OPTIONS (preflight)
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }

    /**
     * Récupère les données JSON de la requête
     */
    protected function getJsonInput(): ?array
    {
        $input = file_get_contents('php://input');
        if (empty($input)) {
            return null;
        }

        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
    }

    /**
     * Extrait le token Bearer de l'header Authorization
     */
    protected function getBearerToken(): ?string
    {
        $headers = getallheaders();
        if (!$headers) {
            return null;
        }

        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    /**
     * Valide un token et retourne l'ID utilisateur
     */
    protected function validateToken(string $token): ?int
    {
        // Vérifier si le token existe dans la session ET correspond au token fourni
        if (
            isset($_SESSION['auth_token']) &&
            isset($_SESSION['user_id']) &&
            $_SESSION['auth_token'] === $token
        ) {
            return (int) $_SESSION['user_id'];
        }

        return null;
    }

    /**
     * Requiert une authentification
     */
    protected function requireAuth(): bool
    {
        $token = $this->getBearerToken();
        if (!$token) {
            $this->sendError('Token d\'authentification manquant', 401);
            return false;
        }

        $userId = $this->validateToken($token);
        if (!$userId) {
            $this->sendError('Token invalide ou expiré', 401);
            return false;
        }

        // Vérifier que l'utilisateur existe et n'est pas banni
        $user = $this->utilisateurService->getUtilisateurById($userId);
        if (!$user || $user->getEstBanni()) {
            $this->sendError('Compte non autorisé', 403);
            return false;
        }

        $_SESSION['current_user_id'] = $userId;
        return true;
    }

    /**
     * Requiert un rôle spécifique
     */
    protected function requireRole(string $role): bool
    {
        if (!$this->requireAuth()) {
            return false;
        }

        $user = $this->utilisateurService->getUtilisateurById($_SESSION['current_user_id']);

        switch ($role) {
            case 'admin':
                if (!$user->isAdministrateur()) {
                    $this->sendError('Permissions administrateur requises', 403);
                    return false;
                }
                break;
            case 'moderator':
                if (!$user->isModerateur() && !$user->isAdministrateur()) {
                    $this->sendError('Permissions de modération requises', 403);
                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * Récupère l'ID de l'utilisateur connecté
     */
    protected function getCurrentUserId(): ?int
    {
        return $_SESSION['current_user_id'] ?? null;
    }

    /**
     * Envoie une réponse de succès
     */
    protected function sendSuccess($data = null, string $message = 'Succès', int $code = 200): void
    {
        http_response_code($code);

        $response = [
            'success' => true,
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Envoie une réponse d'erreur
     */
    protected function sendError(string $message, int $code = 400, $details = null): void
    {
        http_response_code($code);

        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($details !== null) {
            $response['details'] = $details;
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Gestion globale des exceptions
     */
    protected function handleException(\Exception $e): void
    {
        error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

        if ($e instanceof InvalidArgumentException) {
            $this->sendError($e->getMessage(), 400);
        } elseif ($e instanceof RuntimeException) {
            $this->sendError($e->getMessage(), 403);
        } else {
            $this->sendError('Erreur interne du serveur', 500);
        }
    }
}
