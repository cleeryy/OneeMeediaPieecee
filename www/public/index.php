<?php
/**
 * Point d'entrée unique de l'application OneMediaPiece
 * Gère le routage pour l'API REST et les pages HTML statiques
 */

// Démarrage de la session
session_start();

// Configuration des erreurs selon l'environnement
if (defined('APP_ENV') && APP_ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}

// Autoloader (à adapter selon votre configuration)
// Méthode 1 : Si vous utilisez Composer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Méthode 2 : Autoloader manuel simple
spl_autoload_register(function ($className) {
    // Convertir le namespace en chemin de fichier
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    $className = str_replace('App' . DIRECTORY_SEPARATOR, '', $className);
    $file = __DIR__ . '/../src/' . $className . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Chargement de la configuration
if (file_exists(__DIR__ . '/../config/config.php')) {
    require_once __DIR__ . '/../config/config.php';
}

// Import des contrôleurs
use App\Controller\AuthController;
use App\Controller\UtilisateurController;
use App\Controller\ArticleController;
use App\Controller\CommentaireController;
use App\Controller\ModerationController;

// Récupération de l'URI et de la méthode HTTP
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Nettoyage de l'URI (suppression des slashes en trop)
$requestUri = rtrim($requestUri, '/');
if (empty($requestUri)) {
    $requestUri = '/';
}

// Fonction utilitaire pour servir les fichiers statiques
function serveStaticFile($filePath)
{
    if (!file_exists($filePath)) {
        http_response_code(404);
        echo '<!DOCTYPE html>
<html>
<head>
    <title>404 - Page non trouvée</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container text-center" style="margin-top: 100px;">
        <h1>404 - Page non trouvée</h1>
        <p>La page que vous cherchez n\'existe pas.</p>
        <a href="/" class="btn btn-primary">Retour à l\'accueil</a>
    </div>
</body>
</html>';
        return;
    }

    // Déterminer le type MIME
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
    $mimeTypes = [
        'html' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject'
    ];

    $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

    // Headers pour le cache (pour les assets statiques)
    if (in_array($extension, ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot'])) {
        header('Cache-Control: public, max-age=31536000'); // 1 an
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
    }

    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
}

try {
    // ROUTAGE PRINCIPAL
    switch (true) {

        // =============================================================================
        // ROUTES API REST
        // =============================================================================

        // ----------------------- AUTHENTICATION -----------------------
        case preg_match('#^/api/auth/login$#', $requestUri) && $requestMethod === 'POST':
            $controller = new AuthController();
            $controller->login();
            break;

        case preg_match('#^/api/auth/logout$#', $requestUri) && $requestMethod === 'POST':
            $controller = new AuthController();
            $controller->logout();
            break;

        case preg_match('#^/api/auth/refresh$#', $requestUri) && $requestMethod === 'POST':
            $controller = new AuthController();
            $controller->refresh();
            break;

        // ----------------------- UTILISATEURS -----------------------
        case preg_match('#^/api/utilisateur$#', $requestUri) && $requestMethod === 'POST':
            $controller = new UtilisateurController();
            $controller->create();
            break;

        case preg_match('#^/api/utilisateur/(\d+)$#', $requestUri, $matches) && $requestMethod === 'GET':
            $controller = new UtilisateurController();
            $controller->show((int) $matches[1]);
            break;

        case preg_match('#^/api/utilisateur/(\d+)$#', $requestUri, $matches) && $requestMethod === 'PUT':
            $controller = new UtilisateurController();
            $controller->update((int) $matches[1]);
            break;

        case preg_match('#^/api/utilisateur/(\d+)$#', $requestUri, $matches) && $requestMethod === 'DELETE':
            $controller = new UtilisateurController();
            $controller->delete((int) $matches[1]);
            break;

        // ----------------------- ARTICLES -----------------------
        case preg_match('#^/api/article$#', $requestUri) && $requestMethod === 'GET':
            $controller = new ArticleController();
            $controller->index();
            break;

        case preg_match('#^/api/article$#', $requestUri) && $requestMethod === 'POST':
            $controller = new ArticleController();
            $controller->create();
            break;

        case preg_match('#^/api/article/(\d+)$#', $requestUri, $matches) && $requestMethod === 'GET':
            $controller = new ArticleController();
            $controller->show((int) $matches[1]);
            break;

        case preg_match('#^/api/article/(\d+)$#', $requestUri, $matches) && $requestMethod === 'PUT':
            $controller = new ArticleController();
            $controller->update((int) $matches[1]);
            break;

        case preg_match('#^/api/article/(\d+)$#', $requestUri, $matches) && $requestMethod === 'DELETE':
            $controller = new ArticleController();
            $controller->delete((int) $matches[1]);
            break;

        // ----------------------- COMMENTAIRES -----------------------
        case preg_match('#^/api/article/(\d+)/commentaire$#', $requestUri, $matches) && $requestMethod === 'GET':
            $controller = new CommentaireController();
            $controller->indexByArticle((int) $matches[1]);
            break;

        case preg_match('#^/api/article/(\d+)/commentaire$#', $requestUri, $matches) && $requestMethod === 'POST':
            $controller = new CommentaireController();
            $controller->create((int) $matches[1]);
            break;

        case preg_match('#^/api/commentaire/(\d+)$#', $requestUri, $matches) && $requestMethod === 'GET':
            $controller = new CommentaireController();
            $controller->show((int) $matches[1]);
            break;

        case preg_match('#^/api/commentaire/(\d+)$#', $requestUri, $matches) && $requestMethod === 'PUT':
            $controller = new CommentaireController();
            $controller->update((int) $matches[1]);
            break;

        case preg_match('#^/api/commentaire/(\d+)$#', $requestUri, $matches) && $requestMethod === 'DELETE':
            $controller = new CommentaireController();
            $controller->delete((int) $matches[1]);
            break;

        // ----------------------- MODERATION -----------------------
        case preg_match('#^/api/moderation/articles$#', $requestUri) && $requestMethod === 'GET':
            $controller = new ModerationController();
            $controller->getArticlesEnAttente();
            break;

        case preg_match('#^/api/moderation/article/(\d+)$#', $requestUri, $matches) && $requestMethod === 'PUT':
            $controller = new ModerationController();
            $controller->modererArticle((int) $matches[1]);
            break;

        case preg_match('#^/api/moderation/commentaires$#', $requestUri) && $requestMethod === 'GET':
            $controller = new ModerationController();
            $controller->getCommentairesEnAttente();
            break;

        case preg_match('#^/api/moderation/commentaire/(\d+)$#', $requestUri, $matches) && $requestMethod === 'PUT':
            $controller = new ModerationController();
            $controller->modererCommentaire((int) $matches[1]);
            break;

        case preg_match('#^/api/moderation/utilisateur/(\d+)/signaler$#', $requestUri, $matches) && $requestMethod === 'POST':
            $controller = new ModerationController();
            $controller->signalerUtilisateur((int) $matches[1]);
            break;

        case preg_match('#^/api/moderation/utilisateur/(\d+)/bannir$#', $requestUri, $matches) && $requestMethod === 'PUT':
            $controller = new ModerationController();
            $controller->bannirUtilisateur((int) $matches[1]);
            break;

        // =============================================================================
        // ROUTES POUR LES ASSETS STATIQUES
        // =============================================================================

        case preg_match('#^/(css|js|images|img|fonts|assets)/.+$#', $requestUri):
            $filePath = __DIR__ . $requestUri;
            serveStaticFile($filePath);
            break;

        case $requestUri === '/favicon.ico':
            $filePath = __DIR__ . '/images/favicon.ico';
            if (!file_exists($filePath)) {
                // Créer un favicon vide si pas trouvé
                header('Content-Type: image/x-icon');
                header('Content-Length: 0');
            } else {
                serveStaticFile($filePath);
            }
            break;

        // =============================================================================
        // ROUTES POUR LES PAGES HTML
        // =============================================================================

        case $requestUri === '/' || $requestUri === '/index':
            serveStaticFile(__DIR__ . '/index.html');
            break;

        case $requestUri === '/login':
            serveStaticFile(__DIR__ . '/login.html');
            break;

        case $requestUri === '/register':
            serveStaticFile(__DIR__ . '/register.html');
            break;

        case $requestUri === '/dashboard':
            serveStaticFile(__DIR__ . '/dashboard.html');
            break;

        case $requestUri === '/moderation':
            serveStaticFile(__DIR__ . '/moderation.html');
            break;

        case $requestUri === '/profile':
            serveStaticFile(__DIR__ . '/profile.html');
            break;

        case $requestUri === '/create-article':
            serveStaticFile(__DIR__ . '/create-article.html');
            break;

        case $requestUri === '/article-detail':
            serveStaticFile(__DIR__ . '/article-detail.html');
            break;

        case preg_match('#^/article/(\d+)$#', $requestUri, $matches):
            // Redirection vers la page de détail d'article avec l'ID en paramètre
            header("Location: /article-detail.html?id=" . $matches[1]);
            exit;

        case $requestUri === '/about':
            serveStaticFile(__DIR__ . '/about.html');
            break;

        case $requestUri === '/contact':
            serveStaticFile(__DIR__ . '/contact.html');
            break;

        case $requestUri === '/privacy':
            serveStaticFile(__DIR__ . '/privacy.html');
            break;

        case $requestUri === '/terms':
            serveStaticFile(__DIR__ . '/terms.html');
            break;

        // Routes administratives
        case $requestUri === '/admin':
            serveStaticFile(__DIR__ . '/admin.html');
            break;

        case $requestUri === '/admin/users':
            serveStaticFile(__DIR__ . '/admin/users.html');
            break;

        // =============================================================================
        // ROUTE 404 PAR DÉFAUT
        // =============================================================================

        default:
            // Vérifier si c'est une route API non trouvée
            if (strpos($requestUri, '/api/') === 0) {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Endpoint API non trouvé: ' . $requestUri,
                    'method' => $requestMethod
                ]);
            } else {
                // Pour les autres routes, essayer de servir un fichier HTML
                $htmlFile = __DIR__ . $requestUri;
                if (pathinfo($htmlFile, PATHINFO_EXTENSION) !== 'html') {
                    $htmlFile .= '.html';
                }

                if (file_exists($htmlFile)) {
                    serveStaticFile($htmlFile);
                } else {
                    // 404 pour les pages HTML
                    http_response_code(404);
                    echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page non trouvée - OneMediaPiece</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="main-layout">
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="/" class="logo">OneMediaPiece</a>
                <nav class="nav">
                    <ul class="nav-menu">
                        <li><a href="/" class="nav-link">Accueil</a></li>
                        <li><a href="/login.html" class="nav-link">Se connecter</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>
    
    <main class="main-content">
        <div class="container text-center">
            <h1>404 - Page non trouvée</h1>
            <p class="mb-lg">La page que vous cherchez n\'existe pas ou a été déplacée.</p>
            <div class="btn-group">
                <a href="/" class="btn btn-primary">Retour à l\'accueil</a>
                <a href="/login.html" class="btn btn-outline-primary">Se connecter</a>
            </div>
        </div>
    </main>
    
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <p class="footer-copyright">© 2025 OneMediaPiece. Tous droits réservés.</p>
            </div>
        </div>
    </footer>
</body>
</html>';
                }
            }
    }

} catch (\Exception $e) {
    // Gestion globale des erreurs
    error_log('Erreur dans index.php: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    // Réponse selon le type de requête
    if (strpos($requestUri, '/api/') === 0) {
        http_response_code(500);
        header('Content-Type: application/json');

        if (defined('APP_ENV') && APP_ENV === 'development') {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur interne du serveur',
                'debug' => [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur interne du serveur'
            ]);
        }
    } else {
        http_response_code(500);
        echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur - OneMediaPiece</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="main-layout">
    <div class="container text-center" style="margin-top: 100px;">
        <h1>Erreur du serveur</h1>
        <p>Une erreur inattendue s\'est produite. Veuillez réessayer plus tard.</p>
        <a href="/" class="btn btn-primary">Retour à l\'accueil</a>
    </div>
</body>
</html>';
    }
}
?>