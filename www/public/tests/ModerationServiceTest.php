<?php
namespace Tests;

use App\Service\ModerationService;
use App\Service\ArticleService;
use App\Service\UtilisateurService;
use App\Entity\ArticleEntity;
use RuntimeException;
use InvalidArgumentException;

class ModerationServiceTest extends TestBase
{
    private $service;
    private $articleService;
    private $utilisateurService;

    public function __construct()
    {
        $this->setUp();
        $this->service = new ModerationService();
        $this->articleService = new ArticleService();
        $this->utilisateurService = new UtilisateurService();
    }

    public function runTests()
    {
        echo "\n⚖️ TESTS DU SERVICE MODÉRATION\n";
        echo str_repeat("=", 50) . "\n";

        $this->testGetHistoriqueModerations();
        $this->testGetActionsRecentes();
        $this->testGetHistoriqueArticle();
        $this->testGetStatistiquesModerations();
        $this->testGenererRapportPeriode();
        $this->testPermissionsAcces();
    }

    private function testGetHistoriqueModerations()
    {
        echo "\n📋 Test historique modérations:\n";

        $moderateur = $this->getTestUser('moderateur@test.com');
        $admin = $this->getTestUser('admin@test.com');

        // Test accès par modérateur
        $historique = $this->service->getHistoriqueModerations($moderateur->getId());

        $this->assert(
            is_array($historique),
            "Récupération historique par modérateur réussie"
        );

        // Test accès par admin
        $historique = $this->service->getHistoriqueModerations($admin->getId());

        $this->assert(
            is_array($historique),
            "Récupération historique par admin réussie"
        );

        // Test accès par rédacteur
        $redacteur = $this->getTestUser('redacteur@test.com');

        $this->assertException(
            function () use ($redacteur) {
                $this->service->getHistoriqueModerations($redacteur->getId());
            },
            RuntimeException::class,
            "Exception levée pour accès par rédacteur"
        );
    }

    private function testGetActionsRecentes()
    {
        echo "\n🕒 Test actions récentes:\n";

        $moderateur = $this->getTestUser('moderateur@test.com');

        $actions = $this->service->getActionsRecentes($moderateur->getId(), 5);

        $this->assert(
            is_array($actions),
            "Récupération actions récentes réussie"
        );

        $this->assert(
            count($actions) <= 5,
            "Limite d'actions respectée"
        );
    }

    private function testGetHistoriqueArticle()
    {
        echo "\n📰 Test historique article:\n";

        $redacteur = $this->getTestUser('redacteur@test.com');
        $moderateur = $this->getTestUser('moderateur@test.com');

        // Créer un article
        $article = $this->articleService->creerArticle(
            'Article pour historique',
            'Contenu test',
            ArticleEntity::VISIBILITE_PUBLIC,
            $redacteur->getId()
        );

        // Refuser l'article pour créer un historique
        $this->articleService->modererArticle(
            $article->getId(),
            'refuser',
            $moderateur->getId(),
            'Test historique'
        );

        // Récupérer l'historique
        $historique = $this->service->getHistoriqueArticle(
            $article->getId(),
            $moderateur->getId()
        );

        $this->assert(
            is_array($historique),
            "Récupération historique article réussie"
        );
    }

    private function testGetStatistiquesModerations()
    {
        echo "\n📊 Test statistiques modérations:\n";

        $admin = $this->getTestUser('admin@test.com');

        // Test accès par admin
        $stats = $this->service->getStatistiquesModerations($admin->getId());

        $this->assert(
            is_array($stats),
            "Récupération statistiques par admin réussie"
        );

        $this->assert(
            isset($stats['refus_articles']),
            "Statistiques contiennent refus articles"
        );

        $this->assert(
            isset($stats['articles_en_attente']),
            "Statistiques contiennent articles en attente"
        );

        // Test accès par non-admin
        $moderateur = $this->getTestUser('moderateur@test.com');

        $this->assertException(
            function () use ($moderateur) {
                $this->service->getStatistiquesModerations($moderateur->getId());
            },
            RuntimeException::class,
            "Exception levée pour accès statistiques par non-admin"
        );
    }

    private function testGenererRapportPeriode()
    {
        echo "\n📄 Test génération rapport période:\n";

        $admin = $this->getTestUser('admin@test.com');

        $dateDebut = '2025-01-01 00:00:00';
        $dateFin = '2025-12-31 23:59:59';

        // Test génération par admin
        $rapport = $this->service->genererRapportPeriode(
            $dateDebut,
            $dateFin,
            $admin->getId()
        );

        $this->assert(
            is_array($rapport),
            "Génération rapport par admin réussie"
        );

        $this->assert(
            isset($rapport['periode']),
            "Rapport contient informations période"
        );

        $this->assert(
            isset($rapport['resume']),
            "Rapport contient résumé"
        );

        // Test génération par non-admin
        $moderateur = $this->getTestUser('moderateur@test.com');

        $this->assertException(
            function () use ($dateDebut, $dateFin, $moderateur) {
                $this->service->genererRapportPeriode(
                    $dateDebut,
                    $dateFin,
                    $moderateur->getId()
                );
            },
            RuntimeException::class,
            "Exception levée pour génération rapport par non-admin"
        );

        // Test format date invalide
        $this->assertException(
            function () use ($admin) {
                $this->service->genererRapportPeriode(
                    'date_invalide',
                    '2025-12-31 23:59:59',
                    $admin->getId()
                );
            },
            InvalidArgumentException::class,
            "Exception levée pour format date invalide"
        );
    }

    private function testPermissionsAcces()
    {
        echo "\n🔐 Test permissions d'accès:\n";

        $redacteur = $this->getTestUser('redacteur@test.com');

        // Test accès signalements par rédacteur
        $this->assertException(
            function () use ($redacteur) {
                $this->service->getSignalementsNonTraites($redacteur->getId());
            },
            RuntimeException::class,
            "Exception levée pour accès signalements par rédacteur"
        );

        // Test détails modération par rédacteur
        $this->assertException(
            function () use ($redacteur) {
                $this->service->getModerationDetails($redacteur->getId());
            },
            RuntimeException::class,
            "Exception levée pour accès détails par rédacteur"
        );
    }
}
