<?php
namespace Tests;

use App\Service\ArticleService;
use App\Entity\ArticleEntity;
use InvalidArgumentException;
use RuntimeException;

class ArticleServiceTest extends TestBase
{
    private $service;

    public function __construct()
    {
        $this->setUp();
        $this->service = new ArticleService();
    }

    public function runTests()
    {
        echo "\n📰 TESTS DU SERVICE ARTICLE\n";
        echo str_repeat("=", 50) . "\n";

        $this->testCreerArticle();
        $this->testModifierArticle();
        $this->testModererArticle();
        $this->testGetArticleById();
        $this->testGetArticles();
        $this->testPermissionsLecture();
        $this->testSupprimerArticle();
        $this->testRechercherArticles();
    }

    private function testCreerArticle()
    {
        echo "\n📝 Test création d'article:\n";

        $redacteur = $this->getTestUser('redacteur@test.com');
        $moderateur = $this->getTestUser('moderateur@test.com');

        // Test création par rédacteur
        $article = $this->service->creerArticle(
            'Mon premier article',
            'Contenu de l\'article de test',
            ArticleEntity::VISIBILITE_PUBLIC,
            $redacteur->getId()
        );

        $this->assert(
            $article instanceof ArticleEntity,
            "Création d'article par rédacteur réussie"
        );

        $this->assert(
            $article->getEtat() === ArticleEntity::ETAT_EN_ATTENTE,
            "Article de rédacteur en attente de modération"
        );

        // Test création par modérateur
        $article = $this->service->creerArticle(
            'Article de modérateur',
            'Contenu de l\'article de modérateur',
            ArticleEntity::VISIBILITE_PUBLIC,
            $moderateur->getId()
        );

        $this->assert(
            $article->getEtat() === ArticleEntity::ETAT_ACCEPTE,
            "Article de modérateur directement accepté"
        );

        // Test titre vide
        $this->assertException(
            function () use ($redacteur) {
                $this->service->creerArticle(
                    '',
                    'Contenu',
                    ArticleEntity::VISIBILITE_PUBLIC,
                    $redacteur->getId()
                );
            },
            InvalidArgumentException::class,
            "Exception levée pour titre vide"
        );

        // Test contenu vide
        $this->assertException(
            function () use ($redacteur) {
                $this->service->creerArticle(
                    'Titre',
                    '',
                    ArticleEntity::VISIBILITE_PUBLIC,
                    $redacteur->getId()
                );
            },
            InvalidArgumentException::class,
            "Exception levée pour contenu vide"
        );

        // Test visibilité invalide
        $this->assertException(
            function () use ($redacteur) {
                $this->service->creerArticle(
                    'Titre',
                    'Contenu',
                    'invalide',
                    $redacteur->getId()
                );
            },
            InvalidArgumentException::class,
            "Exception levée pour visibilité invalide"
        );
    }

    private function testModifierArticle()
    {
        echo "\n✏️ Test modification d'article:\n";

        $redacteur = $this->getTestUser('redacteur@test.com');

        // Créer un article
        $article = $this->service->creerArticle(
            'Article à modifier',
            'Contenu original',
            ArticleEntity::VISIBILITE_PUBLIC,
            $redacteur->getId()
        );

        // Modifier l'article
        $articleModifie = $this->service->modifierArticle(
            $article->getId(),
            'Article modifié',
            'Contenu modifié',
            ArticleEntity::VISIBILITE_PRIVE,
            $redacteur->getId()
        );

        $this->assert(
            $articleModifie->getTitre() === 'Article modifié',
            "Modification du titre réussie"
        );

        $this->assert(
            $articleModifie->getEtat() === ArticleEntity::ETAT_EN_ATTENTE,
            "Article repasse en modération après modification"
        );

        // Test modification par non-propriétaire
        $autreUser = $this->getTestUser('redacteur2@test.com');

        $this->assertException(
            function () use ($article, $autreUser) {
                $this->service->modifierArticle(
                    $article->getId(),
                    'Titre',
                    'Contenu',
                    ArticleEntity::VISIBILITE_PUBLIC,
                    $autreUser->getId()
                );
            },
            RuntimeException::class,
            "Exception levée pour modification par non-propriétaire"
        );
    }

    private function testModererArticle()
    {
        echo "\n⚖️ Test modération d'article:\n";

        $redacteur = $this->getTestUser('redacteur@test.com');
        $moderateur = $this->getTestUser('moderateur@test.com');

        // Créer un article en attente
        $article = $this->service->creerArticle(
            'Article à modérer',
            'Contenu à modérer',
            ArticleEntity::VISIBILITE_PUBLIC,
            $redacteur->getId()
        );

        // Test acceptation
        $result = $this->service->modererArticle(
            $article->getId(),
            'accepter',
            $moderateur->getId()
        );

        $this->assert(
            $result === true,
            "Acceptation d'article par modérateur réussie"
        );

        // Créer un autre article pour refus
        $article2 = $this->service->creerArticle(
            'Article à refuser',
            'Contenu inapproprié',
            ArticleEntity::VISIBILITE_PUBLIC,
            $redacteur->getId()
        );

        // Test refus
        $result = $this->service->modererArticle(
            $article2->getId(),
            'refuser',
            $moderateur->getId(),
            'Contenu inapproprié'
        );

        $this->assert(
            $result === true,
            "Refus d'article par modérateur réussi"
        );

        // Test refus sans description
        $article3 = $this->service->creerArticle(
            'Article sans description',
            'Contenu',
            ArticleEntity::VISIBILITE_PUBLIC,
            $redacteur->getId()
        );

        $this->assertException(
            function () use ($article3, $moderateur) {
                $this->service->modererArticle(
                    $article3->getId(),
                    'refuser',
                    $moderateur->getId()
                );
            },
            InvalidArgumentException::class,
            "Exception levée pour refus sans description"
        );

        // Test modération par non-modérateur
        $this->assertException(
            function () use ($article3, $redacteur) {
                $this->service->modererArticle(
                    $article3->getId(),
                    'accepter',
                    $redacteur->getId()
                );
            },
            RuntimeException::class,
            "Exception levée pour modération par non-modérateur"
        );
    }

    private function testGetArticleById()
    {
        echo "\n🔍 Test récupération article par ID:\n";

        $redacteur = $this->getTestUser('redacteur@test.com');
        $moderateur = $this->getTestUser('moderateur@test.com');

        // Créer et accepter un article
        $article = $this->service->creerArticle(
            'Article test',
            'Contenu test',
            ArticleEntity::VISIBILITE_PUBLIC,
            $redacteur->getId()
        );

        $this->service->modererArticle(
            $article->getId(),
            'accepter',
            $moderateur->getId()
        );

        // Test récupération
        $articleRecupere = $this->service->getArticleById($article->getId());

        $this->assert(
            $articleRecupere instanceof ArticleEntity,
            "Récupération d'article par ID réussie"
        );

        $this->assert(
            $articleRecupere->getTitre() === 'Article test',
            "Données d'article correctes"
        );

        // Test article inexistant
        $articleInexistant = $this->service->getArticleById(99999);

        $this->assert(
            $articleInexistant === null,
            "Article inexistant retourne null"
        );
    }

    private function testGetArticles()
    {
        echo "\n📄 Test récupération liste d'articles:\n";

        $articles = $this->service->getArticles();

        $this->assert(
            is_array($articles),
            "Récupération liste d'articles retourne un tableau"
        );

        $this->assert(
            count($articles) > 0,
            "La liste contient des articles"
        );
    }

    private function testPermissionsLecture()
    {
        echo "\n🔐 Test permissions de lecture:\n";

        $redacteur = $this->getTestUser('redacteur@test.com');

        // Créer un article privé
        $articlePrive = $this->service->creerArticle(
            'Article privé',
            'Contenu privé',
            ArticleEntity::VISIBILITE_PRIVE,
            $redacteur->getId()
        );

        // Test lecture sans utilisateur connecté
        $article = $this->service->getArticleById($articlePrive->getId(), null);

        $this->assert(
            $article === null,
            "Article privé non visible sans connexion"
        );

        // Test lecture avec utilisateur connecté
        $article = $this->service->getArticleById($articlePrive->getId(), $redacteur->getId());

        $this->assert(
            $article instanceof ArticleEntity,
            "Article privé visible avec connexion"
        );
    }

    private function testSupprimerArticle()
    {
        echo "\n🗑️ Test suppression d'article:\n";

        $redacteur = $this->getTestUser('redacteur@test.com');

        $article = $this->service->creerArticle(
            'Article à supprimer',
            'Contenu à supprimer',
            ArticleEntity::VISIBILITE_PUBLIC,
            $redacteur->getId()
        );

        // Test suppression par propriétaire
        $result = $this->service->supprimerArticle($article->getId(), $redacteur->getId());

        $this->assert(
            $result === true,
            "Suppression d'article par propriétaire réussie"
        );
    }

    private function testRechercherArticles()
    {
        echo "\n🔎 Test recherche d'articles:\n";

        $redacteur = $this->getTestUser('redacteur@test.com');
        $moderateur = $this->getTestUser('moderateur@test.com');

        // Créer et accepter un article avec mot-clé
        $article = $this->service->creerArticle(
            'Article avec mot-clé spécial',
            'Contenu spécial',
            ArticleEntity::VISIBILITE_PUBLIC,
            $redacteur->getId()
        );

        $this->service->modererArticle(
            $article->getId(),
            'accepter',
            $moderateur->getId()
        );

        // Test recherche
        $resultats = $this->service->rechercherArticles('spécial');

        $this->assert(
            is_array($resultats),
            "Recherche retourne un tableau"
        );

        $this->assert(
            count($resultats) > 0,
            "Recherche trouve des résultats"
        );

        // Test terme trop court
        $this->assertException(
            function () {
                $this->service->rechercherArticles('ab');
            },
            InvalidArgumentException::class,
            "Exception levée pour terme de recherche trop court"
        );
    }
}
