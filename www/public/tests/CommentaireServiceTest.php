<?php
namespace Tests;

use App\Service\CommentaireService;
use App\Service\ArticleService;
use App\Entity\CommentaireEntity;
use App\Entity\ArticleEntity;
use InvalidArgumentException;
use RuntimeException;

class CommentaireServiceTest extends TestBase
{
    private $service;
    private $articleService;

    public function __construct()
    {
        $this->setUp();
        $this->service = new CommentaireService();
        $this->articleService = new ArticleService();
    }

    public function runTests()
    {
        echo "\n💬 TESTS DU SERVICE COMMENTAIRE\n";
        echo str_repeat("=", 50) . "\n";

        $this->testCreerCommentaire();
        $this->testModifierCommentaire();
        $this->testModererCommentaire();
        $this->testGetCommentairesArticle();
        $this->testSupprimerCommentaire();
        $this->testValidationContenu();
    }

    private function testCreerCommentaire()
    {
        echo "\n💬 Test création de commentaire:\n";

        $redacteur = $this->getTestUser('redacteur@test.com');
        $moderateur = $this->getTestUser('moderateur@test.com');

        // Créer un article accepté
        $article = $this->articleService->creerArticle(
            'Article pour commentaires',
            'Contenu article',
            ArticleEntity::VISIBILITE_PUBLIC,
            $redacteur->getId()
        );

        $this->articleService->modererArticle(
            $article->getId(),
            'accepter',
            $moderateur->getId()
        );

        // Test création par rédacteur
        $commentaire = $this->service->creerCommentaire(
            'Mon premier commentaire',
            $article->getId(),
            $redacteur->getId()
        );

        $this->assert(
            $commentaire instanceof CommentaireEntity,
            "Création de commentaire par rédacteur réussie"
        );

        $this->assert(
            $commentaire->getEtat() === CommentaireEntity::ETAT_EN_ATTENTE,
            "Commentaire de rédacteur en attente de modération"
        );

        // Test création par modérateur
        $commentaire2 = $this->service->creerCommentaire(
            'Commentaire de modérateur',
            $article->getId(),
            $moderateur->getId()
        );

        $this->assert(
            $commentaire2->getEtat() === CommentaireEntity::ETAT_ACCEPTE,
            "Commentaire de modérateur directement accepté"
        );

        // Test contenu vide
        $this->assertException(
            function () use ($article, $redacteur) {
                $this->service->creerCommentaire(
                    '',
                    $article->getId(),
                    $redacteur->getId()
                );
            },
            InvalidArgumentException::class,
            "Exception levée pour contenu vide"
        );

        // Test article inexistant
        $this->assertException(
            function () use ($redacteur) {
                $this->service->creerCommentaire(
                    'Commentaire',
                    99999,
                    $redacteur->getId()
                );
            },
            RuntimeException::class,
            "Exception levée pour article inexistant"
        );
    }

    private function testModifierCommentaire()
    {
        echo "\n✏️ Test modification de commentaire:\n";

        $redacteur = $this->getTestUser('redacteur@test.com');
        $moderateur = $this->getTestUser('moderateur@test.com');

        // Créer article et commentaire
        $article = $this->articleService->creerArticle(
            'Article test',
            'Contenu',
            ArticleEntity::VISIBILITE_PUBLIC,
            $redacteur->getId()
        );

        $this->articleService->modererArticle(
            $article->getId(),
            'accepter',
            $moderateur->getId()
        );

        $commentaire = $this->service->creerCommentaire(
            'Commentaire original',
            $article->getId(),
            $redacteur->getId()
        );

        // Modifier le commentaire
        $commentaireModifie = $this->service->modifierCommentaire(
            $commentaire->getId(),
            'Commentaire modifié',
            $redacteur->getId()
        );

        $this->assert(
            $commentaireModifie->getContenu() === 'Commentaire modifié',
            "Modification du contenu réussie"
        );

        $this->assert(
            $commentaireModifie->getEtat() === CommentaireEntity::ETAT_EN_ATTENTE,
            "Commentaire repasse en modération après modification"
        );

        // Test modification par non-propriétaire
        $autreUser = $this->getTestUser('redacteur2@test.com');

        $this->assertException(
            function () use ($commentaire, $autreUser) {
                $this->service->modifierCommentaire(
                    $commentaire->getId(),
                    'Modification non autorisée',
                    $autreUser->getId()
                );
            },
            RuntimeException::class,
            "Exception levée pour modification par non-propriétaire"
        );
    }

    private function testModererCommentaire()
    {
        echo "\n⚖️ Test modération de commentaire:\n";

        $redacteur = $this->getTestUser('redacteur@test.com');
        $moderateur = $this->getTestUser('moderateur@test.com');

        // Créer article et commentaire
        $article = $this->articleService->creerArticle(
            'Article test',
            'Contenu',
            ArticleEntity::VISIBILITE_PUBLIC,
            $redacteur->getId()
        );

        $this->articleService->modererArticle(
            $article->getId(),
            'accepter',
            $moderateur->getId()
        );

        $commentaire = $this->service->creerCommentaire(
            'Commentaire à modérer',
            $article->getId(),
            $redacteur->getId()
        );

        // Test acceptation
        $result = $this->service->modererCommentaire(
            $commentaire->getId(),
            'accepter',
            $moderateur->getId()
        );

        $this->assert(
            $result === true,
            "Acceptation de commentaire par modérateur réussie"
        );

        // Créer un autre commentaire pour refus
        $commentaire2 = $this->service->creerCommentaire(
            'Commentaire inapproprié',
            $article->getId(),
            $redacteur->getId()
        );

        // Test refus
        $result = $this->service->modererCommentaire(
            $commentaire2->getId(),
            'refuser',
            $moderateur->getId(),
            'Contenu inapproprié'
        );

        $this->assert(
            $result === true,
            "Refus de commentaire par modérateur réussi"
        );

        // Test modération par non-modérateur
        $commentaire3 = $this->service->creerCommentaire(
            'Autre commentaire',
            $article->getId(),
            $redacteur->getId()
        );

        $this->assertException(
            function () use ($commentaire3, $redacteur) {
                $this->service->modererCommentaire(
                    $commentaire3->getId(),
                    'accepter',
                    $redacteur->getId()
                );
            },
            RuntimeException::class,
            "Exception levée pour modération par non-modérateur"
        );
    }

    private function testGetCommentairesArticle()
    {
        echo "\n📄 Test récupération commentaires d'article:\n";

        $redacteur = $this->getTestUser('redacteur@test.com');
        $moderateur = $this->getTestUser('moderateur@test.com');

        // Créer article
        $article = $this->articleService->creerArticle(
            'Article avec commentaires',
            'Contenu',
            ArticleEntity::VISIBILITE_PUBLIC,
            $redacteur->getId()
        );

        $this->articleService->modererArticle(
            $article->getId(),
            'accepter',
            $moderateur->getId()
        );

        // Créer plusieurs commentaires
        $commentaire1 = $this->service->creerCommentaire(
            'Premier commentaire',
            $article->getId(),
            $redacteur->getId()
        );

        $commentaire2 = $this->service->creerCommentaire(
            'Deuxième commentaire',
            $article->getId(),
            $redacteur->getId()
        );

        // Accepter les commentaires
        $this->service->modererCommentaire(
            $commentaire1->getId(),
            'accepter',
            $moderateur->getId()
        );

        $this->service->modererCommentaire(
            $commentaire2->getId(),
            'accepter',
            $moderateur->getId()
        );

        // Récupérer les commentaires
        $commentaires = $this->service->getCommentairesArticle($article->getId());

        $this->assert(
            is_array($commentaires),
            "Récupération commentaires retourne un tableau"
        );

        $this->assert(
            count($commentaires) >= 2,
            "Le tableau contient les commentaires créés"
        );
    }

    private function testSupprimerCommentaire()
    {
        echo "\n🗑️ Test suppression de commentaire:\n";

        $redacteur = $this->getTestUser('redacteur@test.com');
        $moderateur = $this->getTestUser('moderateur@test.com');

        // Créer article et commentaire
        $article = $this->articleService->creerArticle(
            'Article test',
            'Contenu',
            ArticleEntity::VISIBILITE_PUBLIC,
            $redacteur->getId()
        );

        $this->articleService->modererArticle(
            $article->getId(),
            'accepter',
            $moderateur->getId()
        );

        $commentaire = $this->service->creerCommentaire(
            'Commentaire à supprimer',
            $article->getId(),
            $redacteur->getId()
        );

        // Test suppression par propriétaire
        $result = $this->service->supprimerCommentaire(
            $commentaire->getId(),
            $redacteur->getId()
        );

        $this->assert(
            $result === true,
            "Suppression de commentaire par propriétaire réussie"
        );
    }

    private function testValidationContenu()
    {
        echo "\n✅ Test validation contenu:\n";

        $redacteur = $this->getTestUser('redacteur@test.com');
        $moderateur = $this->getTestUser('moderateur@test.com');

        // Créer un article
        $article = $this->articleService->creerArticle(
            'Article test',
            'Contenu',
            ArticleEntity::VISIBILITE_PUBLIC,
            $redacteur->getId()
        );

        $this->articleService->modererArticle(
            $article->getId(),
            'accepter',
            $moderateur->getId()
        );

        // Test contenu trop court
        $this->assertException(
            function () use ($article, $redacteur) {
                $this->service->creerCommentaire(
                    'AB',
                    $article->getId(),
                    $redacteur->getId()
                );
            },
            InvalidArgumentException::class,
            "Exception levée pour contenu trop court"
        );

        // Test contenu trop long
        $contenuTropLong = str_repeat('A', 10001);

        $this->assertException(
            function () use ($article, $redacteur, $contenuTropLong) {
                $this->service->creerCommentaire(
                    $contenuTropLong,
                    $article->getId(),
                    $redacteur->getId()
                );
            },
            InvalidArgumentException::class,
            "Exception levée pour contenu trop long"
        );
    }
}
