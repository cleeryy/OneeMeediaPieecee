<?php
// Point d'entrée unique de l'application
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Chargement de l'autoloader (à adapter selon votre configuration)
require_once __DIR__ . '/../autoload.php';

use App\Controller\AuthController;
use App\Controller\UtilisateurController;
use App\Controller\ArticleController;
use App\Controller\CommentaireController;
use App\Controller\ModerationController;

// Analyse de l'URL et de la méthode HTTP
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Suppression du préfixe /api si présent
$uri = preg_replace('#^/api#', '', $uri);

try {
    $routeFound = false;

    // Authentication routes
    if (preg_match('#^/auth/login$#', $uri)) {
        $routeFound = true;
        $controller = new AuthController();
        if ($method === 'POST') {
            $controller->login();
        } else {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        }
    } elseif (preg_match('#^/auth/logout$#', $uri)) {
        $routeFound = true;
        $controller = new AuthController();
        if ($method === 'POST') {
            $controller->logout();
        } else {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        }
    } elseif (preg_match('#^/auth/refresh$#', $uri)) {
        $routeFound = true;
        $controller = new AuthController();
        if ($method === 'POST') {
            $controller->refresh();
        } else {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        }
    }

    // Utilisateurs routes
    elseif (preg_match('#^/utilisateur$#', $uri)) {
        $routeFound = true;
        $controller = new UtilisateurController();
        switch ($method) {
            case 'POST':
                $controller->create();
                break;
            default:
                http_response_code(405);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        }
    } elseif (preg_match('#^/utilisateur/(\d+)$#', $uri, $matches)) {
        $routeFound = true;
        $controller = new UtilisateurController();
        $userId = (int) $matches[1];

        switch ($method) {
            case 'GET':
                $controller->show($userId);
                break;
            case 'PUT':
                $controller->update($userId);
                break;
            case 'DELETE':
                $controller->delete($userId);
                break;
            default:
                http_response_code(405);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        }
    }

    // Articles routes
    elseif (preg_match('#^/article$#', $uri)) {
        $routeFound = true;
        $controller = new ArticleController();
        switch ($method) {
            case 'GET':
                $controller->index();
                break;
            case 'POST':
                $controller->create();
                break;
            default:
                http_response_code(405);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        }
    } elseif (preg_match('#^/article/(\d+)$#', $uri, $matches)) {
        $routeFound = true;
        $controller = new ArticleController();
        $articleId = (int) $matches[1];

        switch ($method) {
            case 'GET':
                $controller->show($articleId);
                break;
            case 'PUT':
                $controller->update($articleId);
                break;
            case 'DELETE':
                $controller->delete($articleId);
                break;
            default:
                http_response_code(405);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        }
    }

    // Commentaires routes
    elseif (preg_match('#^/article/(\d+)/commentaire$#', $uri, $matches)) {
        $routeFound = true;
        $controller = new CommentaireController();
        $articleId = (int) $matches[1];

        switch ($method) {
            case 'GET':
                $controller->indexByArticle($articleId);
                break;
            case 'POST':
                $controller->create($articleId);
                break;
            default:
                http_response_code(405);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        }
    } elseif (preg_match('#^/commentaire/(\d+)$#', $uri, $matches)) {
        $routeFound = true;
        $controller = new CommentaireController();
        $commentId = (int) $matches[1];

        switch ($method) {
            case 'PUT':
                $controller->update($commentId);
                break;
            case 'DELETE':
                $controller->delete($commentId);
                break;
            default:
                http_response_code(405);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        }
    }

    // Modération routes
    elseif (preg_match('#^/moderation/articles$#', $uri)) {
        $routeFound = true;
        $controller = new ModerationController();
        if ($method === 'GET') {
            $controller->getArticlesEnAttente();
        } else {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        }
    } elseif (preg_match('#^/moderation/article/(\d+)$#', $uri, $matches)) {
        $routeFound = true;
        $controller = new ModerationController();
        if ($method === 'PUT') {
            $controller->modererArticle((int) $matches[1]);
        } else {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        }
    } elseif (preg_match('#^/moderation/commentaires$#', $uri)) {
        $routeFound = true;
        $controller = new ModerationController();
        if ($method === 'GET') {
            $controller->getCommentairesEnAttente();
        } else {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        }
    } elseif (preg_match('#^/moderation/commentaire/(\d+)$#', $uri, $matches)) {
        $routeFound = true;
        $controller = new ModerationController();
        if ($method === 'PUT') {
            $controller->modererCommentaire((int) $matches[1]);
        } else {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        }
    } elseif (preg_match('#^/moderation/utilisateur/(\d+)/signaler$#', $uri, $matches)) {
        $routeFound = true;
        $controller = new ModerationController();
        if ($method === 'POST') {
            $controller->signalerUtilisateur((int) $matches[1]);
        } else {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        }
    } elseif (preg_match('#^/moderation/utilisateur/(\d+)/bannir$#', $uri, $matches)) {
        $routeFound = true;
        $controller = new ModerationController();
        if ($method === 'PUT') {
            $controller->bannirUtilisateur((int) $matches[1]);
        } else {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        }
    }

    // Route non trouvée
    if (!$routeFound) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Route non trouvée'
        ]);
    }

} catch (\Exception $e) {
    error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur interne du serveur'
    ]);
}
?>