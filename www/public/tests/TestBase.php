<?php
namespace Tests;

use App\Core\Database;
use App\Repository\UtilisateurDAO;
use App\Repository\ArticleDAO;
use App\Repository\CommentaireDAO;
use App\Repository\ModerationDAO;
use App\Entity\UtilisateurEntity;
use App\Entity\ArticleEntity;
use App\Entity\CommentaireEntity;
use Exception;

abstract class TestBase
{
    protected $db;
    protected $utilisateurDAO;
    protected $articleDAO;
    protected $commentaireDAO;
    protected $moderationDAO;

    protected $testResults = [];
    protected $testCount = 0;
    protected $successCount = 0;
    protected $failureCount = 0;

    public function setUp()
    {
        // Configuration pour les tests
        define('DEBUG_MODE', true);

        $this->db = Database::getInstance();
        $this->utilisateurDAO = new UtilisateurDAO();
        $this->articleDAO = new ArticleDAO();
        $this->commentaireDAO = new CommentaireDAO();
        $this->moderationDAO = new ModerationDAO();

        // Nettoyer les données de test
        $this->cleanupTestData();

        // Créer des données de test
        $this->createTestData();
    }

    protected function cleanupTestData()
    {
        try {
            $this->db->execute("DELETE FROM Moderation WHERE 1=1");
            $this->db->execute("DELETE FROM Commentaire WHERE 1=1");
            $this->db->execute("DELETE FROM Article WHERE 1=1");
            $this->db->execute("DELETE FROM Utilisateur WHERE email LIKE '%test%'");
        } catch (Exception $e) {
            // Ignorer les erreurs de nettoyage
        }
    }

    protected function createTestData()
    {
        // Créer des utilisateurs de test
        $this->createTestUsers();
    }

    protected function createTestUsers()
    {
        // Admin de test
        $admin = new UtilisateurEntity();
        $admin->setEmail('admin@test.com')
            ->setMotDePasse(password_hash('password123', PASSWORD_DEFAULT))
            ->setPseudonyme('AdminTest')
            ->setTypeCompte(UtilisateurEntity::TYPE_ADMINISTRATEUR);
        $this->utilisateurDAO->save($admin);

        // Modérateur de test
        $moderateur = new UtilisateurEntity();
        $moderateur->setEmail('moderateur@test.com')
            ->setMotDePasse(password_hash('password123', PASSWORD_DEFAULT))
            ->setPseudonyme('ModerateurTest')
            ->setTypeCompte(UtilisateurEntity::TYPE_MODERATEUR);
        $this->utilisateurDAO->save($moderateur);

        // Rédacteur de test
        $redacteur = new UtilisateurEntity();
        $redacteur->setEmail('redacteur@test.com')
            ->setMotDePasse(password_hash('password123', PASSWORD_DEFAULT))
            ->setPseudonyme('RedacteurTest');
        $this->utilisateurDAO->save($redacteur);

        // Rédacteur de test
        $redacteur2 = new UtilisateurEntity();
        $redacteur2->setEmail('redacteur2@test.com')
            ->setMotDePasse(password_hash('password123', PASSWORD_DEFAULT))
            ->setPseudonyme('RedacteurTest');
        $this->utilisateurDAO->save($redacteur2);
    }

    protected function assert($condition, $message)
    {
        $this->testCount++;
        if ($condition) {
            $this->successCount++;
            $this->testResults[] = ['status' => 'PASS', 'message' => $message];
            echo "✅ PASS: $message\n";
        } else {
            $this->failureCount++;
            $this->testResults[] = ['status' => 'FAIL', 'message' => $message];
            echo "❌ FAIL: $message\n";
        }
    }

    protected function assertException($callback, $expectedExceptionClass, $message)
    {
        $this->testCount++;
        try {
            $callback();
            $this->failureCount++;
            $this->testResults[] = ['status' => 'FAIL', 'message' => "$message - Exception attendue non levée"];
            echo "❌ FAIL: $message - Exception attendue non levée\n";
        } catch (Exception $e) {
            if (get_class($e) === $expectedExceptionClass || is_subclass_of($e, $expectedExceptionClass)) {
                $this->successCount++;
                $this->testResults[] = ['status' => 'PASS', 'message' => $message];
                echo "✅ PASS: $message\n";
            } else {
                $this->failureCount++;
                $this->testResults[] = ['status' => 'FAIL', 'message' => "$message - Exception inattendue: " . get_class($e)];
                echo "❌ FAIL: $message - Exception inattendue: " . get_class($e) . "\n";
            }
        }
    }

    protected function getTestUser($email)
    {
        return $this->utilisateurDAO->findByEmail($email);
    }

    abstract public function runTests();

    public function getResults()
    {
        return [
            'total' => $this->testCount,
            'success' => $this->successCount,
            'failure' => $this->failureCount,
            'details' => $this->testResults
        ];
    }
}
