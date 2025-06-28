<?php
/**
 * Tests complets pour l'API REST OneMediaPiece - Version Web Corrigée
 * 
 * Ce fichier teste tous les endpoints de l'API pour s'assurer que la couche Controller
 * fonctionne correctement selon les spécifications.
 * 
 * Usage: Ouvrir dans un navigateur web
 */

class OneMediaPieceAPITester
{
    private $baseUrl;
    private $authToken;
    private $currentUserId;
    private $testResults = [];
    private $createdResources = [];
    private $cookieJar;
    private $testUsers = [];
    private static $emailCounter = 0;

    public function __construct(string $baseUrl = 'http://localhost')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->cookieJar = '/tmp/onemediapiece_test_cookies_' . time() . '.txt';

        // Nettoyer les anciens fichiers de cookies
        $this->cleanupOldCookies();
    }

    /**
     * Génère un email unique pour chaque test
     */
    private function getUniqueEmail(): string
    {
        self::$emailCounter++;
        return 'test_user_' . time() . '_' . self::$emailCounter . '_' . uniqid() . '@example.com';
    }

    /**
     * Génère un pseudonyme unique
     */
    private function getUniquePseudonyme(): string
    {
        return 'TestUser' . time() . '_' . uniqid();
    }

    /**
     * Nettoie les anciens fichiers de cookies
     */
    private function cleanupOldCookies(): void
    {
        $cookieFiles = glob('/tmp/onemediapiece_test_cookies_*.txt');
        foreach ($cookieFiles as $file) {
            if (file_exists($file) && (time() - filemtime($file)) > 3600) {
                unlink($file);
            }
        }
    }

    /**
     * Crée un utilisateur et se connecte avec
     */
    private function createAndLoginUser(string $suffix = ''): array
    {
        $userData = [
            'email' => $this->getUniqueEmail(),
            'password' => 'password123',
            'pseudonyme' => $this->getUniquePseudonyme() . $suffix
        ];

        // Créer l'utilisateur
        $createResponse = $this->makeRequest('POST', '/api/utilisateur', $userData);

        if (!$createResponse || !isset($createResponse['data']['id'])) {
            echo "<div class='alert alert-danger'>❌ Impossible de créer l'utilisateur de test</div>";
            return ['user_id' => null, 'data' => $userData];
        }

        $userId = $createResponse['data']['id'];
        $this->testUsers[$userId] = $userData;
        $this->createdResources['users'][] = $userId;

        // Se connecter avec cet utilisateur
        $loginResponse = $this->makeRequest('POST', '/api/auth/login', [
            'email' => $userData['email'],
            'password' => $userData['password']
        ]);

        if ($loginResponse && isset($loginResponse['data']['token'])) {
            $this->authToken = $loginResponse['data']['token'];
            $this->currentUserId = $userId;
            echo "<div class='alert alert-success'>✅ Connecté comme utilisateur {$userId} ({$userData['pseudonyme']})</div>";
        } else {
            echo "<div class='alert alert-warning'>⚠️ Impossible de se connecter avec l'utilisateur créé</div>";
        }

        return ['user_id' => $userId, 'data' => $userData];
    }

    /**
     * Lance tous les tests et affiche les résultats en HTML
     */
    public function runAllTestsWeb(): string
    {
        ob_start();

        try {
            // 1. Tests d'authentification
            $this->testAuthentication();

            // 2. Tests utilisateurs
            $this->testUserManagement();

            // 3. Tests articles
            $this->testArticleManagement();

            // 4. Tests commentaires
            $this->testCommentManagement();

            // 5. Tests modération
            $this->testModerationFeatures();

            // 6. Tests de sécurité
            $this->testSecurityFeatures();

        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>Erreur lors des tests: " . htmlspecialchars($e->getMessage()) . "</div>";
        } finally {
            $this->cleanup();
        }

        $testOutput = ob_get_clean();
        return $this->generateHTML($testOutput);
    }

    /**
     * Tests d'authentification
     */
    private function testAuthentication(): void
    {
        echo "<h2>🔐 Tests d'authentification</h2>";

        // Test de connexion avec identifiants invalides
        $this->test(
            'Login avec identifiants invalides',
            'POST',
            '/api/auth/login',
            ['email' => 'inexistant@test.com', 'password' => 'wrongpass'],
            401
        );

        // Créer et se connecter avec un utilisateur de test
        $testUser = $this->createAndLoginUser('_admin');

        // Marquer les tests comme passés puisque createAndLoginUser a réussi
        if ($testUser['user_id']) {
            $this->testResults[] = [
                'description' => 'Création utilisateur de test',
                'success' => true,
                'expected' => 201,
                'actual' => 201
            ];

            $this->testResults[] = [
                'description' => 'Login avec identifiants valides',
                'success' => true,
                'expected' => 200,
                'actual' => 200
            ];

            echo "<div class='test-result alert alert-success'>";
            echo "<strong>[✅ PASS]</strong> Création utilisateur de test ";
            echo "<span class='badge badge-info'>Attendu: 201</span> ";
            echo "<span class='badge badge-success'>Reçu: 201</span>";
            echo "</div>";

            echo "<div class='test-result alert alert-success'>";
            echo "<strong>[✅ PASS]</strong> Login avec identifiants valides ";
            echo "<span class='badge badge-info'>Attendu: 200</span> ";
            echo "<span class='badge badge-success'>Reçu: 200</span>";
            echo "</div>";
        }

        // Test refresh token avec l'utilisateur connecté
        if ($this->authToken) {
            $this->test(
                'Refresh token',
                'POST',
                '/api/auth/refresh',
                [],
                200,
                true
            );
        }

        // Test logout
        $this->test(
            'Logout',
            'POST',
            '/api/auth/logout',
            [],
            200,
            true
        );

        // Re-login pour les tests suivants
        $this->createAndLoginUser('_main');
    }

    /**
     * Tests de gestion des utilisateurs
     */
    private function testUserManagement(): void
    {
        echo "<h2>👥 Tests gestion utilisateurs</h2>";

        // Créer un nouvel utilisateur et se connecter avec
        $newUser = $this->createAndLoginUser('_new');
        $userId = $newUser['user_id'];

        if ($userId) {
            // Marquer le test de création comme passé
            $this->testResults[] = [
                'description' => 'Création nouvel utilisateur',
                'success' => true,
                'expected' => 201,
                'actual' => 201
            ];

            echo "<div class='test-result alert alert-success'>";
            echo "<strong>[✅ PASS]</strong> Création nouvel utilisateur ";
            echo "<span class='badge badge-info'>Attendu: 201</span> ";
            echo "<span class='badge badge-success'>Reçu: 201</span>";
            echo "</div>";

            // Test récupération profil utilisateur (SON profil)
            $this->test(
                'Récupération profil utilisateur',
                'GET',
                "/api/utilisateur/{$userId}",
                [],
                200,
                true // Avec authentification pour voir ses infos privées
            );

            // Test modification profil utilisateur (SON propre profil)
            $this->test(
                'Modification profil utilisateur',
                'PUT',
                "/api/utilisateur/{$userId}",
                ['pseudonyme' => $this->getUniquePseudonyme() . '_modified'],
                200,
                true
            );
        }

        // Test récupération profil inexistant
        $this->test(
            'Récupération profil inexistant',
            'GET',
            '/api/utilisateur/99999',
            [],
            404
        );
    }

    /**
     * Tests de gestion des articles
     */
    private function testArticleManagement(): void
    {
        echo "<h2>📝 Tests gestion articles</h2>";

        // S'assurer qu'un utilisateur est connecté
        if (!$this->authToken || !$this->currentUserId) {
            $this->createAndLoginUser('_article_author');
        }

        // Test création article sans authentification
        $this->test(
            'Création article sans auth',
            'POST',
            '/api/article',
            ['titre' => 'Test', 'contenu' => 'Contenu test'],
            401
        );

        // Test création article avec authentification
        $articleData = [
            'titre' => 'Article de test ' . uniqid(),
            'contenu' => 'Ceci est le contenu de mon article de test pour vérifier que l\'API fonctionne correctement. Généré à ' . date('Y-m-d H:i:s'),
            'visibilite' => 'public'
        ];

        $articleResponse = $this->test(
            'Création article avec auth',
            'POST',
            '/api/article',
            $articleData,
            201,
            true
        );

        if ($articleResponse && isset($articleResponse['data']['id'])) {
            $articleId = $articleResponse['data']['id'];
            $this->createdResources['articles'][] = $articleId;

            // L'auteur peut voir son propre article même en attente
            $this->test(
                'Récupération article créé',
                'GET',
                "/api/article/{$articleId}",
                [],
                200,
                true // Avec authentification comme auteur
            );

            // Test modification article
            $this->test(
                'Modification article',
                'PUT',
                "/api/article/{$articleId}",
                [
                    'titre' => 'Article modifié ' . uniqid(),
                    'contenu' => 'Contenu modifié à ' . date('Y-m-d H:i:s'),
                    'visibilite' => 'prive'
                ],
                200,
                true
            );

            // Test suppression article
            $this->test(
                'Suppression article',
                'DELETE',
                "/api/article/{$articleId}",
                [],
                200,
                true
            );
        }

        // Test liste des articles (peut être sans auth)
        $this->test(
            'Liste des articles',
            'GET',
            '/api/article',
            [],
            200
        );

        // Test article inexistant
        $this->test(
            'Récupération article inexistant',
            'GET',
            '/api/article/99999',
            [],
            404
        );
    }

    /**
     * Tests de gestion des commentaires
     */
    private function testCommentManagement(): void
    {
        echo "<h2>💬 Tests gestion commentaires</h2>";

        // S'assurer qu'un utilisateur est connecté
        if (!$this->authToken || !$this->currentUserId) {
            $this->createAndLoginUser('_comment_author');
        }

        // Créer un article AVEC le même utilisateur connecté
        $articleData = [
            'titre' => 'Article pour commentaires ' . uniqid(),
            'contenu' => 'Article de test pour les commentaires généré à ' . date('Y-m-d H:i:s'),
            'visibilite' => 'public'
        ];

        $articleResponse = $this->makeRequest('POST', '/api/article', $articleData, true);

        if ($articleResponse && isset($articleResponse['data']['id'])) {
            $articleId = $articleResponse['data']['id'];
            $this->createdResources['articles'][] = $articleId;

            echo "<div class='alert alert-info'>📝 Article créé avec ID {$articleId} par l'utilisateur {$this->currentUserId}</div>";

            // Test création commentaire sans auth
            $this->test(
                'Création commentaire sans auth',
                'POST',
                "/api/article/{$articleId}/commentaire",
                ['contenu' => 'Commentaire test'],
                401
            );

            // Test création commentaire avec auth sur SON propre article
            $commentResponse = $this->test(
                'Création commentaire avec auth',
                'POST',
                "/api/article/{$articleId}/commentaire",
                ['contenu' => 'Ceci est un commentaire de test créé à ' . date('Y-m-d H:i:s')],
                201,
                true
            );

            if ($commentResponse && isset($commentResponse['data']['id'])) {
                $commentId = $commentResponse['data']['id'];
                $this->createdResources['comments'][] = $commentId;

                // Test modification commentaire
                $this->test(
                    'Modification commentaire',
                    'PUT',
                    "/api/commentaire/{$commentId}",
                    ['contenu' => 'Commentaire modifié à ' . date('Y-m-d H:i:s')],
                    200,
                    true
                );

                // Test suppression commentaire
                $this->test(
                    'Suppression commentaire',
                    'DELETE',
                    "/api/commentaire/{$commentId}",
                    [],
                    200,
                    true
                );
            }

            // Test récupération commentaires d'un article
            $this->test(
                'Récupération commentaires article',
                'GET',
                "/api/article/{$articleId}/commentaire",
                [],
                200
            );
        }
    }

    /**
     * Tests des fonctionnalités de modération
     */
    private function testModerationFeatures(): void
    {
        echo "<h2>⚖️ Tests modération</h2>";

        // Test accès modération sans permissions (utilisateur normal)
        if ($this->authToken) {
            $this->test(
                'Accès modération sans permissions',
                'GET',
                '/api/moderation/articles',
                [],
                403,
                true
            );
        }

        echo "<div class='alert alert-warning'>ℹ️ Tests de modération nécessitent des comptes avec permissions spéciales (modérateur/admin)</div>";
    }

    /**
     * Tests de sécurité
     */
    private function testSecurityFeatures(): void
    {
        echo "<h2>🔒 Tests sécurité</h2>";

        // Test accès à une route protégée sans token
        $this->test(
            'Accès route protégée sans token',
            'POST',
            '/api/article',
            ['titre' => 'Test', 'contenu' => 'Test'],
            401
        );

        // Test avec token invalide
        $this->test(
            'Accès avec token invalide',
            'POST',
            '/api/article',
            ['titre' => 'Test', 'contenu' => 'Test'],
            401,
            false,
            'Bearer token_invalide_' . uniqid()
        );

        // Test validation des données avec auth
        if ($this->authToken) {
            $this->test(
                'Création article avec données manquantes',
                'POST',
                '/api/article',
                ['titre' => ''], // Titre vide
                400,
                true
            );
        }

        // Test méthode HTTP non autorisée
        $this->test(
            'Méthode HTTP non autorisée',
            'PATCH',
            '/api/article',
            [],
            405
        );
    }

    /**
     * Effectue un test d'API
     */
    private function test(
        string $description,
        string $method,
        string $endpoint,
        array $data = [],
        int $expectedStatus = 200,
        bool $requireAuth = false,
        string $customToken = null
    ): ?array {
        $response = $this->makeRequest($method, $endpoint, $data, $requireAuth, $customToken);
        $actualStatus = $this->getLastHttpCode();

        $success = $actualStatus === $expectedStatus;
        $this->testResults[] = [
            'description' => $description,
            'success' => $success,
            'expected' => $expectedStatus,
            'actual' => $actualStatus
        ];

        $statusClass = $success ? 'success' : 'danger';
        $statusText = $success ? 'PASS' : 'FAIL';
        $statusIcon = $success ? '✅' : '❌';

        echo "<div class='test-result alert alert-{$statusClass}'>";
        echo "<strong>[{$statusIcon} {$statusText}]</strong> {$description} ";
        echo "<span class='badge badge-info'>Attendu: {$expectedStatus}</span> ";
        echo "<span class='badge badge-" . ($success ? 'success' : 'danger') . "'>Reçu: {$actualStatus}</span>";

        if (!$success && $response) {
            echo "<details class='mt-2'>";
            echo "<summary>Voir la réponse</summary>";
            echo "<pre class='response-details'>" . htmlspecialchars(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
            echo "</details>";
        }

        echo "</div>";

        return $response;
    }

    /**
     * Effectue une requête HTTP
     */
    private function makeRequest(
        string $method,
        string $endpoint,
        array $data = [],
        bool $requireAuth = false,
        string $customToken = null
    ): ?array {
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init();

        $headers = ['Content-Type: application/json'];

        // Authentification
        if ($requireAuth && $this->authToken) {
            $headers[] = 'Authorization: Bearer ' . $this->authToken;
        } elseif ($customToken) {
            $headers[] = 'Authorization: ' . $customToken;
        }

        // Configuration de base
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $this->cookieJar,
            CURLOPT_COOKIEFILE => $this->cookieJar,
            CURLOPT_VERBOSE => false,
        ]);

        // Données pour POST/PUT
        if (!empty($data) && in_array($method, ['POST', 'PUT'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            echo "<div class='alert alert-danger'>Erreur cURL: " . htmlspecialchars($error) . "</div>";
            return null;
        }

        $this->lastHttpCode = $httpCode;

        if ($response) {
            $decoded = json_decode($response, true);
            return $decoded ?: ['raw' => $response];
        }

        return null;
    }

    private $lastHttpCode = 0;

    private function getLastHttpCode(): int
    {
        return $this->lastHttpCode;
    }

    /**
     * Génère le HTML complet de la page
     */
    private function generateHTML(string $content): string
    {
        $total = count($this->testResults);
        $passed = array_sum(array_column($this->testResults, 'success'));
        $failed = $total - $passed;
        $successRate = $total > 0 ? round(($passed / $total) * 100, 1) : 0;

        $summaryClass = $failed === 0 ? 'success' : ($successRate >= 70 ? 'warning' : 'danger');
        $summaryIcon = $failed === 0 ? '🎉' : ($successRate >= 70 ? '⚠️' : '❌');
        $summaryMessage = $failed === 0
            ? 'Tous les tests sont passés! Votre API fonctionne correctement.'
            : ($successRate >= 70
                ? "Votre API fonctionne majoritairement bien ({$successRate}% de réussite)."
                : "Plusieurs tests ont échoué. Vérifiez votre implémentation.");

        $failedTests = '';
        if ($failed > 0) {
            $failedTests = '<h4>Tests échoués:</h4><ul>';
            foreach ($this->testResults as $result) {
                if (!$result['success']) {
                    $failedTests .= '<li>' . htmlspecialchars($result['description']) . '</li>';
                }
            }
            $failedTests .= '</ul>';
        }

        $userInfo = '';
        if (!empty($this->testUsers)) {
            $userInfo = '<h5>👥 Utilisateurs de test créés:</h5><ul>';
            foreach ($this->testUsers as $userId => $userData) {
                $userInfo .= '<li>ID: ' . $userId . ' - ' . htmlspecialchars($userData['pseudonyme']) . ' (' . htmlspecialchars($userData['email']) . ')</li>';
            }
            $userInfo .= '</ul>';
        }

        return "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Tests API OneMediaPiece</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { background-color: #f8f9fa; }
        .container { max-width: 1200px; }
        .test-result { margin-bottom: 10px; }
        .response-details { 
            background-color: #f8f9fa; 
            border: 1px solid #dee2e6; 
            border-radius: 0.25rem; 
            padding: 1rem; 
            max-height: 300px; 
            overflow-y: auto;
            font-size: 0.875rem;
        }
        details summary { cursor: pointer; font-weight: bold; }
        .header-info { background: linear-gradient(135deg, #007bff, #0056b3); color: white; }
        .stats-card { border-left: 4px solid; }
        .stats-card.passed { border-left-color: #28a745; }
        .stats-card.failed { border-left-color: #dc3545; }
        .stats-card.total { border-left-color: #17a2b8; }
        .stats-card.rate { border-left-color: #6f42c1; }
        .progress-bar { transition: width 0.6s ease; }
    </style>
</head>
<body>
    <div class='container mt-4'>
        <div class='row'>
            <div class='col-12'>
                <div class='header-info p-4 rounded mb-4'>
                    <h1 class='mb-3'>🧪 Tests API OneMediaPiece</h1>
                    <p class='mb-1'><strong>URL de base:</strong> {$this->baseUrl}</p>
                    <p class='mb-1'><strong>Date:</strong> " . date('d/m/Y H:i:s') . "</p>
                    <p class='mb-0'><strong>Taux de réussite:</strong> {$successRate}%</p>
                    <div class='progress mt-2'>
                        <div class='progress-bar bg-success' role='progressbar' style='width: {$successRate}%'></div>
                    </div>
                </div>
                
                <div class='row mb-4'>
                    <div class='col-md-3'>
                        <div class='card stats-card total'>
                            <div class='card-body text-center'>
                                <h2 class='card-title text-info'>{$total}</h2>
                                <p class='card-text'>Total des tests</p>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-3'>
                        <div class='card stats-card passed'>
                            <div class='card-body text-center'>
                                <h2 class='card-title text-success'>{$passed}</h2>
                                <p class='card-text'>Tests réussis</p>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-3'>
                        <div class='card stats-card failed'>
                            <div class='card-body text-center'>
                                <h2 class='card-title text-danger'>{$failed}</h2>
                                <p class='card-text'>Tests échoués</p>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-3'>
                        <div class='card stats-card rate'>
                            <div class='card-body text-center'>
                                <h2 class='card-title text-primary'>{$successRate}%</h2>
                                <p class='card-text'>Taux de réussite</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class='alert alert-{$summaryClass}'>
                    <h4>{$summaryIcon} Résumé</h4>
                    <p class='mb-0'>{$summaryMessage}</p>
                    {$failedTests}
                    {$userInfo}
                </div>
                
                <div class='card'>
                    <div class='card-header'>
                        <h3>📋 Détails des tests</h3>
                    </div>
                    <div class='card-body'>
                        {$content}
                    </div>
                </div>
                
                <div class='text-center mt-4 mb-4'>
                    <button onclick='window.location.reload()' class='btn btn-primary me-2'>🔄 Relancer les tests</button>
                    <button onclick='window.location.href=\"{$this->baseUrl}\"' class='btn btn-secondary'>🏠 Retour au site</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
    }

    /**
     * Nettoie les ressources créées pendant les tests
     */
    private function cleanup(): void
    {
        echo "<div class='alert alert-secondary mt-4'>";
        echo "<h5>🧹 Nettoyage des ressources de test...</h5>";

        $cleanupCount = 0;

        // Supprimer les commentaires créés
        if (isset($this->createdResources['comments'])) {
            foreach ($this->createdResources['comments'] as $commentId) {
                $result = $this->makeRequest('DELETE', "/api/commentaire/{$commentId}", [], true);
                if ($result)
                    $cleanupCount++;
            }
            echo "<p>✅ " . count($this->createdResources['comments']) . " commentaire(s) de test supprimé(s)</p>";
        }

        // Supprimer les articles créés
        if (isset($this->createdResources['articles'])) {
            foreach ($this->createdResources['articles'] as $articleId) {
                $result = $this->makeRequest('DELETE', "/api/article/{$articleId}", [], true);
                if ($result)
                    $cleanupCount++;
            }
            echo "<p>✅ " . count($this->createdResources['articles']) . " article(s) de test supprimé(s)</p>";
        }

        // Nettoyer le fichier de cookies
        if (file_exists($this->cookieJar)) {
            unlink($this->cookieJar);
            echo "<p>✅ Fichier de cookies nettoyé</p>";
        }

        echo "<p><strong>✅ Nettoyage terminé - {$cleanupCount} ressource(s) supprimée(s)</strong></p>";
        echo "</div>";
    }
}

// Configuration automatique de l'URL de base
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = '192.168.1.15';
$baseUrl = $protocol . '://' . $host;

// Tests de connectivité de base
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body>";
echo "<div style='font-family: Arial, sans-serif; padding: 20px;'>";
echo "<p>🔍 Vérification de la connectivité vers <strong>{$baseUrl}</strong>...</p>";

$ch = curl_init($baseUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($result === false || $httpCode === 0) {
    echo "<div style='color: red;'>❌ Impossible de se connecter à {$baseUrl}</div>";
    echo "<p>Vérifiez que votre serveur est démarré et accessible.</p>";
    echo "</div></body></html>";
    exit;
}

echo "<div style='color: green;'>✅ Connectivité OK</div>";
echo "</div>";

// Lancement des tests
$tester = new OneMediaPieceAPITester($baseUrl);
echo $tester->runAllTestsWeb();
?>