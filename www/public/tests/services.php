<?php
require_once __DIR__ . '/../../autoload.php';
require_once __DIR__ . '/TestBase.php';
require_once __DIR__ . '/UtilisateurServiceTest.php';
require_once __DIR__ . '/ArticleServiceTest.php';
require_once __DIR__ . '/CommentaireServiceTest.php';
require_once __DIR__ . '/ModerationServiceTest.php';

use Tests\UtilisateurServiceTest;
use Tests\ArticleServiceTest;
use Tests\CommentaireServiceTest;
use Tests\ModerationServiceTest;

// Configuration pour les tests
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Début des tests
echo "🚀 DÉMARRAGE DE LA SUITE DE TESTS ONEMEDIACPIECE</br>";
echo str_repeat("=", 60) . "</br>";
echo "Date: " . date('Y-m-d H:i:s') . "</br></br>";

$totalTests = 0;
$totalSuccess = 0;
$totalFailures = 0;
$allResults = [];

try {
    // Tests Service Utilisateur
    $utilisateurTest = new UtilisateurServiceTest();
    $utilisateurTest->runTests();
    $results = $utilisateurTest->getResults();
    $allResults['UtilisateurService'] = $results;
    $totalTests += $results['total'];
    $totalSuccess += $results['success'];
    $totalFailures += $results['failure'];

    // Tests Service Article
    $articleTest = new ArticleServiceTest();
    $articleTest->runTests();
    $results = $articleTest->getResults();
    $allResults['ArticleService'] = $results;
    $totalTests += $results['total'];
    $totalSuccess += $results['success'];
    $totalFailures += $results['failure'];

    // Tests Service Commentaire
    $commentaireTest = new CommentaireServiceTest();
    $commentaireTest->runTests();
    $results = $commentaireTest->getResults();
    $allResults['CommentaireService'] = $results;
    $totalTests += $results['total'];
    $totalSuccess += $results['success'];
    $totalFailures += $results['failure'];

    // Tests Service Modération
    $moderationTest = new ModerationServiceTest();
    $moderationTest->runTests();
    $results = $moderationTest->getResults();
    $allResults['ModerationService'] = $results;
    $totalTests += $results['total'];
    $totalSuccess += $results['success'];
    $totalFailures += $results['failure'];

} catch (Exception $e) {
    echo "</br>❌ ERREUR CRITIQUE DANS LES TESTS: " . $e->getMessage() . "</br>";
    echo "Trace: " . $e->getTraceAsString() . "</br>";
}

// Rapport final
echo "</br></br>📊 RAPPORT FINAL DES TESTS</br>";
echo str_repeat("=", 60) . "</br>";

foreach ($allResults as $serviceName => $serviceResults) {
    $percentage = $serviceResults['total'] > 0 ?
        round(($serviceResults['success'] / $serviceResults['total']) * 100, 2) : 0;

    echo sprintf(
        "📋 %s: %d/%d tests réussis (%.2f%%)</br>",
        $serviceName,
        $serviceResults['success'],
        $serviceResults['total'],
        $percentage
    );
}

$globalPercentage = $totalTests > 0 ? round(($totalSuccess / $totalTests) * 100, 2) : 0;

echo "</br>🎯 RÉSULTAT GLOBAL:</br>";
echo sprintf("Total: %d tests</br>", $totalTests);
echo sprintf("✅ Réussis: %d</br>", $totalSuccess);
echo sprintf("❌ Échoués: %d</br>", $totalFailures);
echo sprintf("📈 Taux de réussite: %.2f%%</br>", $globalPercentage);

if ($totalFailures === 0) {
    echo "</br>🎉 TOUS LES TESTS SONT PASSÉS AVEC SUCCÈS!</br>";
    echo "Votre code est prêt pour la production.</br>";
} else {
    echo "</br>⚠️ DES TESTS ONT ÉCHOUÉ!</br>";
    echo "Veuillez corriger les erreurs avant de continuer.</br>";
}

// Générer un rapport HTML
generateHtmlReport($allResults, $totalTests, $totalSuccess, $totalFailures, $globalPercentage);

function generateHtmlReport($results, $total, $success, $failures, $percentage)
{
    $html = "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Rapport de Tests - OneMediaPiece</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #007bff; }
        .card.success { border-left-color: #28a745; }
        .card.danger { border-left-color: #dc3545; }
        .service-results { margin-bottom: 30px; }
        .test-item { padding: 10px; margin: 5px 0; border-radius: 4px; }
        .test-pass { background-color: #d4edda; color: #155724; }
        .test-fail { background-color: #f8d7da; color: #721c24; }
        .progress-bar { width: 100%; height: 20px; background-color: #e9ecef; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; background-color: #28a745; transition: width 0.3s ease; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🧪 Rapport de Tests OneMediaPiece</h1>
            <p>Généré le " . date('d/m/Y à H:i:s') . "</p>
        </div>
        
        <div class='summary'>
            <div class='card'>
                <h3>📊 Total des tests</h3>
                <h2>$total</h2>
            </div>
            <div class='card success'>
                <h3>✅ Tests réussis</h3>
                <h2>$success</h2>
            </div>
            <div class='card danger'>
                <h3>❌ Tests échoués</h3>
                <h2>$failures</h2>
            </div>
            <div class='card'>
                <h3>📈 Taux de réussite</h3>
                <h2>$percentage%</h2>
                <div class='progress-bar'>
                    <div class='progress-fill' style='width: $percentage%'></div>
                </div>
            </div>
        </div>";

    foreach ($results as $serviceName => $serviceResults) {
        $servicePercentage = $serviceResults['total'] > 0 ?
            round(($serviceResults['success'] / $serviceResults['total']) * 100, 2) : 0;

        $html .= "<div class='service-results'>
            <h2>📋 $serviceName</h2>
            <p>{$serviceResults['success']}/{$serviceResults['total']} tests réussis ($servicePercentage%)</p>";

        foreach ($serviceResults['details'] as $test) {
            $class = $test['status'] === 'PASS' ? 'test-pass' : 'test-fail';
            $icon = $test['status'] === 'PASS' ? '✅' : '❌';
            $html .= "<div class='test-item $class'>$icon {$test['message']}</div>";
        }

        $html .= "</div>";
    }

    $html .= "</div></body></html>";

    file_put_contents(__DIR__ . '/rapport_tests.html', $html);
    echo "</br>📄 Rapport HTML généré: tests/rapport_tests.html</br>";
}

echo "</br>" . str_repeat("=", 60) . "</br>";
echo "🏁 FIN DES TESTS</br>";
?>