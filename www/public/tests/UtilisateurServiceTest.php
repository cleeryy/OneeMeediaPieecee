<?php
namespace Tests;

use App\Service\UtilisateurService;
use App\Entity\UtilisateurEntity;
use InvalidArgumentException;
use RuntimeException;

class UtilisateurServiceTest extends TestBase
{
    private $service;

    public function __construct()
    {
        $this->setUp();
        $this->service = new UtilisateurService();
    }

    public function runTests()
    {
        echo "\n🧪 TESTS DU SERVICE UTILISATEUR\n";
        echo str_repeat("=", 50) . "\n";

        $this->testCreerCompte();
        $this->testAuthentifier();
        $this->testValiderCompte();
        $this->testChangerTypeCompte();
        $this->testSignalerUtilisateur();
        $this->testBannirUtilisateur();
        $this->testFermerCompte();
        $this->testMettreAJourProfil();
        $this->testValidationDonnees();
    }

    private function testCreerCompte()
    {
        echo "\n📝 Test création de compte:\n";

        // Test création normale
        $utilisateur = $this->service->creerCompte(
            'nouveau@test.com',
            'password123',
            'NouveauUser'
        );

        $this->assert(
            $utilisateur instanceof UtilisateurEntity,
            "Création d'un nouveau compte réussie"
        );

        $this->assert(
            $utilisateur->getTypeCompte() === UtilisateurEntity::TYPE_REDACTEUR,
            "Type par défaut est rédacteur"
        );

        $this->assert(
            !$utilisateur->getEstBanni(),
            "Utilisateur non banni par défaut"
        );

        // Test email déjà utilisé
        $this->assertException(
            function () {
                $this->service->creerCompte(
                    'nouveau@test.com',
                    'password123',
                    'AutrePseudo'
                );
            },
            InvalidArgumentException::class,
            "Exception levée pour email déjà utilisé"
        );

        // Test pseudonyme déjà utilisé
        $this->assertException(
            function () {
                $this->service->creerCompte(
                    'autre@test.com',
                    'password123',
                    'NouveauUser'
                );
            },
            InvalidArgumentException::class,
            "Exception levée pour pseudonyme déjà utilisé"
        );
    }

    private function testAuthentifier()
    {
        echo "\n🔐 Test authentification:\n";

        // Test authentification réussie
        $utilisateur = $this->service->authentifier('admin@test.com', 'password123');

        $this->assert(
            $utilisateur instanceof UtilisateurEntity,
            "Authentification réussie avec bons identifiants"
        );

        $this->assert(
            $utilisateur->getEmail() === 'admin@test.com',
            "Email correct récupéré"
        );

        // Test mauvais mot de passe
        $utilisateur = $this->service->authentifier('admin@test.com', 'mauvais_password');

        $this->assert(
            $utilisateur === null,
            "Échec authentification avec mauvais mot de passe"
        );

        // Test email inexistant
        $utilisateur = $this->service->authentifier('inexistant@test.com', 'password123');

        $this->assert(
            $utilisateur === null,
            "Échec authentification avec email inexistant"
        );

        // Test utilisateur banni
        $redacteur = $this->getTestUser('redacteur@test.com');
        $admin = $this->getTestUser('admin@test.com');
        $this->service->bannirUtilisateur($redacteur->getId(), $admin->getId(), "Test");

        $this->assertException(
            function () {
                $this->service->authentifier('redacteur@test.com', 'password123');
            },
            RuntimeException::class,
            "Exception levée pour utilisateur banni"
        );
    }

    private function testValiderCompte()
    {
        echo "\n✅ Test validation de compte:\n";

        $admin = $this->getTestUser('admin@test.com');
        $redacteur = $this->getTestUser('redacteur@test.com');

        // Test validation par admin
        $result = $this->service->validerCompte($redacteur->getId(), $admin->getId());

        $this->assert(
            $result === true,
            "Validation de compte par admin réussie"
        );

        // Test validation par non-admin
        $moderateur = $this->getTestUser('moderateur@test.com');

        $this->assertException(
            function () use ($redacteur, $moderateur) {
                $this->service->validerCompte($redacteur->getId(), $moderateur->getId());
            },
            RuntimeException::class,
            "Exception levée pour validation par non-admin"
        );
    }

    private function testChangerTypeCompte()
    {
        echo "\n🔄 Test changement type de compte:\n";

        $admin = $this->getTestUser('admin@test.com');
        $redacteur = $this->getTestUser('redacteur@test.com');

        // Test changement par admin
        $result = $this->service->changerTypeCompte(
            $redacteur->getId(),
            UtilisateurEntity::TYPE_MODERATEUR,
            $admin->getId()
        );

        $this->assert(
            $result === true,
            "Changement type par admin réussi"
        );

        // Vérifier le changement
        $redacteurMaj = $this->utilisateurDAO->findById($redacteur->getId());
        $this->assert(
            $redacteurMaj->getTypeCompte() === UtilisateurEntity::TYPE_MODERATEUR,
            "Type de compte effectivement changé"
        );

        $this->service->changerTypeCompte(
            $redacteur->getId(),
            UtilisateurEntity::TYPE_REDACTEUR,
            $admin->getId()
        );
    }

    private function testSignalerUtilisateur()
    {
        echo "\n🚨 Test signalement utilisateur:\n";

        $moderateur = $this->getTestUser('moderateur@test.com');
        $redacteur = $this->getTestUser('redacteur@test.com');

        // Test signalement par modérateur
        $result = $this->service->signalerUtilisateur(
            $redacteur->getId(),
            $moderateur->getId(),
            "Comportement inapproprié"
        );

        $this->assert(
            $result === true,
            "Signalement par modérateur réussi"
        );

        // Test signalement par non-modérateur
        $this->assertException(
            function () use ($redacteur) {
                $this->service->signalerUtilisateur(
                    $redacteur->getId(),
                    $redacteur->getId(),
                    "Test"
                );
            },
            RuntimeException::class,
            "Exception levée pour signalement par non-modérateur"
        );
    }

    private function testBannirUtilisateur()
    {
        echo "\n🔨 Test bannissement utilisateur:\n";

        $admin = $this->getTestUser('admin@test.com');
        $redacteur = $this->getTestUser('redacteur@test.com');

        // Test bannissement par admin
        $result = $this->service->bannirUtilisateur(
            $redacteur->getId(),
            $admin->getId(),
            "Violation des règles"
        );

        $this->assert(
            $result === true,
            "Bannissement par admin réussi"
        );

        // Vérifier le bannissement
        $redacteurMaj = $this->utilisateurDAO->findById($redacteur->getId());
        $this->assert(
            $redacteurMaj->getEstBanni() === true,
            "Utilisateur effectivement banni"
        );
    }

    private function testFermerCompte()
    {
        echo "\n🗑️ Test fermeture de compte:\n";

        $redacteur = $this->getTestUser('redacteur@test.com');

        // Test fermeture sans suppression contenus
        $result = $this->service->fermerCompte($redacteur->getId(), false);

        $this->assert(
            $result === true,
            "Fermeture de compte sans suppression réussie"
        );
    }

    private function testMettreAJourProfil()
    {
        echo "\n📝 Test mise à jour profil:\n";

        $redacteur = $this->getTestUser('redacteur@test.com');

        // Test mise à jour email
        $utilisateur = $this->service->mettreAJourProfil($redacteur->getId(), [
            'email' => 'nouveau_email@test.com'
        ]);

        $this->assert(
            $utilisateur->getEmail() === 'nouveau_email@test.com',
            "Mise à jour email réussie"
        );

        // Test email déjà utilisé
        $this->assertException(
            function () use ($redacteur) {
                $this->service->mettreAJourProfil($redacteur->getId(), [
                    'email' => 'admin@test.com'
                ]);
            },
            InvalidArgumentException::class,
            "Exception levée pour email déjà utilisé"
        );
    }

    private function testValidationDonnees()
    {
        echo "\n✅ Test validation des données:\n";

        // Test email invalide
        $this->assertException(
            function () {
                $this->service->creerCompte('email_invalide', 'password123', 'Test');
            },
            InvalidArgumentException::class,
            "Exception levée pour email invalide"
        );

        // Test mot de passe trop court
        $this->assertException(
            function () {
                $this->service->creerCompte('test@example.com', '123', 'Test');
            },
            InvalidArgumentException::class,
            "Exception levée pour mot de passe trop court"
        );

        // Test pseudonyme trop court
        $this->assertException(
            function () {
                $this->service->creerCompte('test@example.com', 'password123', 'AB');
            },
            InvalidArgumentException::class,
            "Exception levée pour pseudonyme trop court"
        );
    }
}
