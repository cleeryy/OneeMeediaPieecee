<?php
// test-dao-complet.php
// À placer dans public/ pour un test exhaustif de tous les DAO

require_once __DIR__ . '/../autoload.php';

use App\Repository\UtilisateurDAO;
use App\Repository\ArticleDAO;
use App\Repository\CommentaireDAO;
use App\Repository\ModerationDAO;
use App\Entity\UtilisateurEntity;
use App\Entity\ArticleEntity;
use App\Entity\CommentaireEntity;
use App\Entity\ModerationEntity;

// Configuration pour affichage complet
ini_set('max_execution_time', 300); // 5 minutes
ini_set('memory_limit', '256M');

echo "<!DOCTYPE html><html><head><title>Test DAO Complet OneMediaPiece</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .data { background: #fff; padding: 10px; margin: 5px 0; border: 1px solid #ddd; }
    pre { background: #f8f8f8; padding: 10px; overflow-x: auto; }
</style></head><body>";

echo "<h1>🧪 Test Exhaustif des DAO - OneMediaPiece</h1>";
echo "<p class='info'>Tests de toutes les fonctionnalités des DAO avec création de données complètes.</p>";

// Variables globales pour stocker les objets créés
$utilisateurs = [];
$articles = [];
$commentaires = [];
$moderations = [];

// Compteurs pour les statistiques
$testsExecutes = 0;
$testsReussis = 0;
$testsEchecs = 0;

function afficherResultat($nom, $resultat, $donnees = null)
{
    global $testsExecutes, $testsReussis, $testsEchecs;
    $testsExecutes++;

    if ($resultat) {
        $testsReussis++;
        echo "<div class='success'>✅ $nom</div>";
        if ($donnees) {
            echo "<div class='data'><pre>" . print_r($donnees, true) . "</pre></div>";
        }
    } else {
        $testsEchecs++;
        echo "<div class='error'>❌ $nom</div>";
    }
}

try {
    // ========================================
    // TESTS UTILISATEUR DAO
    // ========================================
    echo "<div class='section'><h2>👤 Tests UtilisateurDAO</h2>";

    $utilisateurDao = new UtilisateurDAO();

    // Test 1: Création d'utilisateurs avec différents rôles
    echo "<h3>Création d'utilisateurs</h3>";

    // Administrateur
    $admin = new UtilisateurEntity();
    $admin->setEmail('admin@onemediapiece.com')
        ->setMotDePasse(password_hash('admin123', PASSWORD_DEFAULT))
        ->setPseudonyme('AdminTest')
        ->setTypeCompte(UtilisateurEntity::TYPE_ADMINISTRATEUR)
        ->setEstBanni(false);
    $admin = $utilisateurDao->save($admin);
    $utilisateurs['admin'] = $admin;
    afficherResultat("Création administrateur", $admin->getId() > 0, $admin->toArray());

    // Modérateur
    $moderateur = new UtilisateurEntity();
    $moderateur->setEmail('moderateur@onemediapiece.com')
        ->setMotDePasse(password_hash('mod123', PASSWORD_DEFAULT))
        ->setPseudonyme('ModTest')
        ->setTypeCompte(UtilisateurEntity::TYPE_MODERATEUR)
        ->setEstBanni(false);
    $moderateur = $utilisateurDao->save($moderateur);
    $utilisateurs['moderateur'] = $moderateur;
    afficherResultat("Création modérateur", $moderateur->getId() > 0, $moderateur->toArray());

    // Rédacteurs
    for ($i = 1; $i <= 3; $i++) {
        $redacteur = new UtilisateurEntity();
        $redacteur->setEmail("redacteur$i@onemediapiece.com")
            ->setMotDePasse(password_hash("redacteur$i", PASSWORD_DEFAULT))
            ->setPseudonyme("Redacteur$i")
            ->setTypeCompte(UtilisateurEntity::TYPE_REDACTEUR)
            ->setEstBanni(false);
        $redacteur = $utilisateurDao->save($redacteur);
        $utilisateurs["redacteur$i"] = $redacteur;
        afficherResultat("Création rédacteur $i", $redacteur->getId() > 0);
    }

    // Test 2: Recherches par critères
    echo "<h3>Recherches utilisateurs</h3>";

    $userById = $utilisateurDao->findById($admin->getId());
    afficherResultat("Recherche par ID", $userById && $userById->getId() == $admin->getId());

    $userByEmail = $utilisateurDao->findByEmail('admin@onemediapiece.com');
    afficherResultat("Recherche par email", $userByEmail && $userByEmail->getEmail() == 'admin@onemediapiece.com');

    $userByPseudo = $utilisateurDao->findByPseudonyme('AdminTest');
    afficherResultat("Recherche par pseudonyme", $userByPseudo && $userByPseudo->getPseudonyme() == 'AdminTest');

    $allUsers = $utilisateurDao->findAll();
    afficherResultat("Recherche tous utilisateurs", count($allUsers) >= 5, "Nombre d'utilisateurs: " . count($allUsers));

    $admins = $utilisateurDao->findByType(UtilisateurEntity::TYPE_ADMINISTRATEUR);
    afficherResultat("Recherche administrateurs", count($admins) >= 1, "Nombre d'admins: " . count($admins));

    $redacteurs = $utilisateurDao->findByType(UtilisateurEntity::TYPE_REDACTEUR);
    afficherResultat("Recherche rédacteurs", count($redacteurs) >= 3, "Nombre de rédacteurs: " . count($redacteurs));

    // Test 3: Validation d'unicité
    echo "<h3>Tests de validation</h3>";

    $emailUnique = $utilisateurDao->isEmailUnique('nouveau@test.com');
    afficherResultat("Email unique (nouveau)", $emailUnique);

    $emailExistant = $utilisateurDao->isEmailUnique('admin@onemediapiece.com');
    afficherResultat("Email existant (non unique)", !$emailExistant);

    $pseudoUnique = $utilisateurDao->isPseudonnymeUnique('NouveauPseudo');
    afficherResultat("Pseudonyme unique", $pseudoUnique);

    $pseudoExistant = $utilisateurDao->isPseudonnymeUnique('AdminTest');
    afficherResultat("Pseudonyme existant (non unique)", !$pseudoExistant);

    // Test 4: Modifications d'utilisateurs
    echo "<h3>Modifications utilisateurs</h3>";

    $changementType = $utilisateurDao->changerTypeCompte($utilisateurs['redacteur1']->getId(), UtilisateurEntity::TYPE_MODERATEUR);
    afficherResultat("Changement type de compte", $changementType);

    $bannissement = $utilisateurDao->bannir($utilisateurs['redacteur2']->getId(), true);
    afficherResultat("Bannissement utilisateur", $bannissement);

    $rehabilitation = $utilisateurDao->bannir($utilisateurs['redacteur2']->getId(), false);
    afficherResultat("Réhabilitation utilisateur", $rehabilitation);

    // Test 5: Fonctionnalités avancées
    echo "<h3>Fonctionnalités avancées</h3>";

    $usersPagines = $utilisateurDao->findAllPaginated(1, 3);
    afficherResultat("Pagination utilisateurs", count($usersPagines) <= 3, "Page 1, 3 par page: " . count($usersPagines));

    $usersRecents = $utilisateurDao->findRecentUsers(2);
    afficherResultat("Utilisateurs récents", count($usersRecents) <= 2, "2 plus récents: " . count($usersRecents));

    $totalUsers = $utilisateurDao->countAll();
    afficherResultat("Comptage total", $totalUsers >= 5, "Total: $totalUsers");

    $usersWithStats = $utilisateurDao->findWithStats(10);
    afficherResultat("Utilisateurs avec stats", is_array($usersWithStats), "Avec jointures: " . count($usersWithStats));

    echo "</div>";

    // ========================================
    // TESTS ARTICLE DAO
    // ========================================
    echo "<div class='section'><h2>📄 Tests ArticleDAO</h2>";

    $articleDao = new ArticleDAO();

    // Test 1: Création d'articles variés
    echo "<h3>Création d'articles</h3>";

    $articlesData = [
        ['titre' => 'Guide débutant One Piece', 'contenu' => 'Un guide complet pour débuter dans One Piece...', 'visibilite' => ArticleEntity::VISIBILITE_PUBLIC, 'auteur' => 'redacteur1'],
        ['titre' => 'Théories sur le One Piece', 'contenu' => 'Mes théories personnelles sur ce qu\'est le One Piece...', 'visibilite' => ArticleEntity::VISIBILITE_PRIVE, 'auteur' => 'redacteur2'],
        ['titre' => 'Review dernier chapitre', 'contenu' => 'Mon avis sur le dernier chapitre paru...', 'visibilite' => ArticleEntity::VISIBILITE_PUBLIC, 'auteur' => 'redacteur3'],
        ['titre' => 'Article modérateur', 'contenu' => 'Article écrit par un modérateur...', 'visibilite' => ArticleEntity::VISIBILITE_PUBLIC, 'auteur' => 'moderateur'],
        ['titre' => 'Article admin', 'contenu' => 'Article écrit par un admin...', 'visibilite' => ArticleEntity::VISIBILITE_PUBLIC, 'auteur' => 'admin'],
    ];

    foreach ($articlesData as $index => $data) {
        $article = new ArticleEntity();
        $article->setTitre($data['titre'])
            ->setContenu($data['contenu'])
            ->setVisibilite($data['visibilite'])
            ->setEtat(ArticleEntity::ETAT_EN_ATTENTE)
            ->setUtilisateurId($utilisateurs[$data['auteur']]->getId());
        $article = $articleDao->save($article);
        $articles[$index] = $article;
        afficherResultat("Création article: {$data['titre']}", $article->getId() > 0);
    }

    // Test 2: Recherches d'articles
    echo "<h3>Recherches d'articles</h3>";

    $articleById = $articleDao->findById($articles[0]->getId());
    afficherResultat("Recherche article par ID", $articleById && $articleById->getId() == $articles[0]->getId());

    $allArticles = $articleDao->findAll();
    afficherResultat("Tous les articles", count($allArticles) >= 5, "Nombre total: " . count($allArticles));

    $articlesPublics = $articleDao->findPublic();
    afficherResultat("Articles publics", is_array($articlesPublics), "Articles publics: " . count($articlesPublics));

    $articlesPrives = $articleDao->findPrive();
    afficherResultat("Articles privés", is_array($articlesPrives), "Articles privés: " . count($articlesPrives));

    $articlesEnAttente = $articleDao->findEnAttente();
    afficherResultat("Articles en attente", count($articlesEnAttente) >= 1, "En attente: " . count($articlesEnAttente));

    $articlesByUser = $articleDao->findByUtilisateur($utilisateurs['redacteur1']->getId());
    afficherResultat("Articles par utilisateur", count($articlesByUser) >= 1, "Par redacteur1: " . count($articlesByUser));

    // Test 3: Modifications d'état
    echo "<h3>Modération d'articles</h3>";

    $acceptation = $articleDao->changerEtat($articles[0]->getId(), ArticleEntity::ETAT_ACCEPTE);
    afficherResultat("Acceptation article", $acceptation);

    $refus = $articleDao->changerEtat($articles[1]->getId(), ArticleEntity::ETAT_REFUSE);
    afficherResultat("Refus article", $refus);

    $suppression = $articleDao->delete($articles[2]->getId());
    afficherResultat("Suppression article (soft delete)", $suppression);

    // Test 4: Fonctionnalités avancées
    echo "<h3>Fonctionnalités avancées</h3>";

    $articlesPagines = $articleDao->findAllPaginated(1, 3);
    afficherResultat("Pagination articles", count($articlesPagines) <= 3, "Page 1, 3 par page: " . count($articlesPagines));

    $articlesRecents = $articleDao->findRecentArticles(3);
    afficherResultat("Articles récents", count($articlesRecents) <= 3, "3 plus récents: " . count($articlesRecents));

    $rechercheTitre = $articleDao->findByTitre('One Piece');
    afficherResultat("Recherche par titre", is_array($rechercheTitre), "Résultats recherche: " . count($rechercheTitre));

    $articlesWithAuthor = $articleDao->findWithAuthorDetails(5);
    afficherResultat("Articles avec auteur", is_array($articlesWithAuthor), "Avec jointures: " . count($articlesWithAuthor));

    // Test 5: Comptages
    echo "<h3>Statistiques articles</h3>";

    $totalArticles = $articleDao->countAll();
    afficherResultat("Comptage total articles", $totalArticles >= 5, "Total: $totalArticles");

    $countByUser = $articleDao->countByUtilisateur($utilisateurs['redacteur1']->getId());
    afficherResultat("Comptage par utilisateur", $countByUser >= 1, "Par redacteur1: $countByUser");

    $countAcceptes = $articleDao->countByEtat(ArticleEntity::ETAT_ACCEPTE);
    afficherResultat("Comptage acceptés", $countAcceptes >= 1, "Acceptés: $countAcceptes");

    echo "</div>";

    // ========================================
    // TESTS COMMENTAIRE DAO
    // ========================================
    echo "<div class='section'><h2>💬 Tests CommentaireDAO</h2>";

    $commentaireDao = new CommentaireDAO();

    // Test 1: Création de commentaires
    echo "<h3>Création de commentaires</h3>";

    $commentairesData = [
        ['contenu' => 'Excellent article ! Merci pour ce partage.', 'auteur' => 'redacteur2', 'article' => 0],
        ['contenu' => 'Je ne suis pas d\'accord avec cette théorie...', 'auteur' => 'redacteur3', 'article' => 0],
        ['contenu' => 'Très bon point de vue, j\'avais pas pensé à ça.', 'auteur' => 'moderateur', 'article' => 0],
        ['contenu' => 'Article intéressant mais manque de sources.', 'auteur' => 'redacteur1', 'article' => 1],
        ['contenu' => 'Complètement d\'accord avec l\'auteur !', 'auteur' => 'admin', 'article' => 1],
    ];

    foreach ($commentairesData as $index => $data) {
        $commentaire = new CommentaireEntity();
        $commentaire->setContenu($data['contenu'])
            ->setEtat(CommentaireEntity::ETAT_EN_ATTENTE)
            ->setUtilisateurId($utilisateurs[$data['auteur']]->getId())
            ->setArticleId($articles[$data['article']]->getId());
        $commentaire = $commentaireDao->save($commentaire);
        $commentaires[$index] = $commentaire;
        afficherResultat("Création commentaire $index", $commentaire->getId() > 0);
    }

    // Test 2: Recherches de commentaires
    echo "<h3>Recherches de commentaires</h3>";

    $commentById = $commentaireDao->findById($commentaires[0]->getId());
    afficherResultat("Recherche commentaire par ID", $commentById && $commentById->getId() == $commentaires[0]->getId());

    $allCommentaires = $commentaireDao->findAll();
    afficherResultat("Tous les commentaires", count($allCommentaires) >= 5, "Nombre total: " . count($allCommentaires));

    $commentsByArticle = $commentaireDao->findByArticle($articles[0]->getId());
    afficherResultat("Commentaires par article", count($commentsByArticle) >= 1, "Sur article 0: " . count($commentsByArticle));

    $commentsByUser = $commentaireDao->findByUtilisateur($utilisateurs['redacteur2']->getId());
    afficherResultat("Commentaires par utilisateur", count($commentsByUser) >= 1, "Par redacteur2: " . count($commentsByUser));

    $commentsEnAttente = $commentaireDao->findEnAttente();
    afficherResultat("Commentaires en attente", count($commentsEnAttente) >= 1, "En attente: " . count($commentsEnAttente));

    // Test 3: Modération de commentaires
    echo "<h3>Modération de commentaires</h3>";

    $acceptationComment = $commentaireDao->changerEtat($commentaires[0]->getId(), CommentaireEntity::ETAT_ACCEPTE);
    afficherResultat("Acceptation commentaire", $acceptationComment);

    $refusComment = $commentaireDao->changerEtat($commentaires[1]->getId(), CommentaireEntity::ETAT_REFUSE);
    afficherResultat("Refus commentaire", $refusComment);

    $suppressionComment = $commentaireDao->delete($commentaires[2]->getId());
    afficherResultat("Suppression commentaire", $suppressionComment);

    // Test 4: Fonctionnalités avancées
    echo "<h3>Fonctionnalités avancées</h3>";

    $commentsPagines = $commentaireDao->findAllPaginated(1, 3);
    afficherResultat("Pagination commentaires", count($commentsPagines) <= 3, "Page 1, 3 par page: " . count($commentsPagines));

    $commentsRecents = $commentaireDao->findRecentCommentaires(3);
    afficherResultat("Commentaires récents", count($commentsRecents) <= 3, "3 plus récents: " . count($commentsRecents));

    $commentsWithDetails = $commentaireDao->findWithDetails(5);
    afficherResultat("Commentaires avec détails", is_array($commentsWithDetails), "Avec jointures: " . count($commentsWithDetails));

    // Test 5: Comptages
    echo "<h3>Statistiques commentaires</h3>";

    $countByArticle = $commentaireDao->countByArticle($articles[0]->getId());
    afficherResultat("Comptage par article", $countByArticle >= 0, "Sur article 0: $countByArticle");

    $countByUserComment = $commentaireDao->countByUtilisateur($utilisateurs['redacteur2']->getId());
    afficherResultat("Comptage par utilisateur", $countByUserComment >= 1, "Par redacteur2: $countByUserComment");

    $countAcceptesComment = $commentaireDao->countByEtat(CommentaireEntity::ETAT_ACCEPTE);
    afficherResultat("Comptage acceptés", $countAcceptesComment >= 1, "Acceptés: $countAcceptesComment");

    echo "</div>";

    // ========================================
    // TESTS MODERATION DAO
    // ========================================
    echo "<div class='section'><h2>⚖️ Tests ModerationDAO</h2>";

    $moderationDao = new ModerationDAO();

    // Test 1: Création d'actions de modération
    echo "<h3>Création d'actions de modération</h3>";

    $refusArticleMod = $moderationDao->enregistrerRefusArticle(
        $articles[1]->getId(),
        $utilisateurs['moderateur']->getId(),
        'Contenu inapproprié pour le public cible'
    );
    $moderations[] = $refusArticleMod;
    afficherResultat("Refus article avec traçabilité", $refusArticleMod->getId() > 0, $refusArticleMod->toArray());

    $refusCommentaireMod = $moderationDao->enregistrerRefusCommentaire(
        $commentaires[1]->getId(),
        $utilisateurs['moderateur']->getId(),
        'Commentaire hors sujet'
    );
    $moderations[] = $refusCommentaireMod;
    afficherResultat("Refus commentaire avec traçabilité", $refusCommentaireMod->getId() > 0);

    $signalementMod = $moderationDao->enregistrerSignalement(
        $utilisateurs['redacteur3']->getId(),
        $utilisateurs['moderateur']->getId(),
        'Comportement suspect dans les commentaires'
    );
    $moderations[] = $signalementMod;
    afficherResultat("Signalement utilisateur", $signalementMod->getId() > 0);

    $suppressionCompteMod = $moderationDao->enregistrerSuppressionCompte(
        $utilisateurs['redacteur3']->getId(),
        $utilisateurs['admin']->getId(),
        'Compte supprimé à la demande de l\'utilisateur'
    );
    $moderations[] = $suppressionCompteMod;
    afficherResultat("Suppression compte avec traçabilité", $suppressionCompteMod->getId() > 0);

    // Test 2: Recherches de modérations
    echo "<h3>Recherches de modérations</h3>";

    $modById = $moderationDao->findById($refusArticleMod->getId());
    afficherResultat("Recherche modération par ID", $modById && $modById->getId() == $refusArticleMod->getId());

    $allModerations = $moderationDao->findAll();
    afficherResultat("Toutes les modérations", count($allModerations) >= 4, "Nombre total: " . count($allModerations));

    $modsByModerateur = $moderationDao->findByModerateur($utilisateurs['moderateur']->getId());
    afficherResultat("Modérations par modérateur", count($modsByModerateur) >= 3, "Par modérateur: " . count($modsByModerateur));

    $modsByType = $moderationDao->findByTypeAction(ModerationEntity::TYPE_REFUS_ARTICLE);
    afficherResultat("Modérations par type", count($modsByType) >= 1, "Refus articles: " . count($modsByType));

    $modsByCibleUser = $moderationDao->findByCibleUtilisateur($utilisateurs['redacteur3']->getId());
    afficherResultat("Modérations ciblant utilisateur", count($modsByCibleUser) >= 2, "Ciblant redacteur3: " . count($modsByCibleUser));

    $modsByCibleArticle = $moderationDao->findByCibleArticle($articles[1]->getId());
    afficherResultat("Modérations ciblant article", count($modsByCibleArticle) >= 1, "Ciblant article 1: " . count($modsByCibleArticle));

    $modsByCibleComment = $moderationDao->findByCibleCommentaire($commentaires[1]->getId());
    afficherResultat("Modérations ciblant commentaire", count($modsByCibleComment) >= 1, "Ciblant commentaire 1: " . count($modsByCibleComment));

    // Test 3: Fonctionnalités avancées
    echo "<h3>Fonctionnalités avancées</h3>";

    $modsRecentes = $moderationDao->findRecentActions(5);
    afficherResultat("Actions récentes", count($modsRecentes) <= 5, "5 plus récentes: " . count($modsRecentes));

    $modsPaginees = $moderationDao->findAllPaginated(1, 3);
    afficherResultat("Pagination modérations", count($modsPaginees) <= 3, "Page 1, 3 par page: " . count($modsPaginees));

    $modsWithDetails = $moderationDao->findWithDetails(10);
    afficherResultat("Modérations avec détails", is_array($modsWithDetails), "Avec jointures: " . count($modsWithDetails));

    $dateDebut = date('Y-m-d H:i:s', strtotime('-1 hour'));
    $dateFin = date('Y-m-d H:i:s');
    $modsByPeriode = $moderationDao->findByPeriode($dateDebut, $dateFin);
    afficherResultat("Modérations par période", is_array($modsByPeriode), "Dernière heure: " . count($modsByPeriode));

    // Test 4: Comptages et statistiques
    echo "<h3>Statistiques modérations</h3>";

    $totalMods = $moderationDao->countAll();
    afficherResultat("Comptage total modérations", $totalMods >= 4, "Total: $totalMods");

    $countByMod = $moderationDao->countByModerateur($utilisateurs['moderateur']->getId());
    afficherResultat("Comptage par modérateur", $countByMod >= 3, "Par modérateur: $countByMod");

    $countByType = $moderationDao->countByTypeAction(ModerationEntity::TYPE_SIGNALEMENT);
    afficherResultat("Comptage par type", $countByType >= 1, "Signalements: $countByType");

    echo "</div>";

    // ========================================
    // TESTS DE GESTION D'ERREURS
    // ========================================
    echo "<div class='section'><h2>🚨 Tests de gestion d'erreurs</h2>";

    // Test des exceptions
    echo "<h3>Tests d'exceptions</h3>";

    try {
        $utilisateurDao->findById(-1);
        afficherResultat("ID négatif utilisateur", false);
    } catch (Exception $e) {
        afficherResultat("Exception ID négatif utilisateur", true, $e->getMessage());
    }

    try {
        $articleDao->changerEtat(999999, 'etat_inexistant');
        afficherResultat("État invalide article", false);
    } catch (Exception $e) {
        afficherResultat("Exception état invalide article", true, $e->getMessage());
    }

    try {
        $utilisateurDao->changerTypeCompte($admin->getId(), 'type_inexistant');
        afficherResultat("Type compte invalide", false);
    } catch (Exception $e) {
        afficherResultat("Exception type compte invalide", true, $e->getMessage());
    }

    try {
        $moderationDao->findByTypeAction('type_inexistant');
        afficherResultat("Type modération invalide", false);
    } catch (Exception $e) {
        afficherResultat("Exception type modération invalide", true, $e->getMessage());
    }

    echo "</div>";

    // ========================================
    // TESTS DE TRANSACTIONS ET ROLLBACK
    // ========================================
    echo "<div class='section'><h2>🔄 Tests de transactions</h2>";

    // Simuler une transaction qui échoue
    echo "<h3>Tests de rollback</h3>";

    // On va tenter de créer un utilisateur avec des données invalides
    try {
        $userInvalide = new UtilisateurEntity();
        $userInvalide->setEmail('') // Email vide, doit échouer
            ->setMotDePasse('')
            ->setPseudonyme('')
            ->setTypeCompte('type_invalide');
        $result = $utilisateurDao->save($userInvalide);
        afficherResultat("Transaction invalide (ne devrait pas réussir)", false);
    } catch (Exception $e) {
        afficherResultat("Rollback transaction invalide", true, $e->getMessage());
    }

    echo "</div>";

    // ========================================
    // TESTS DE PERFORMANCE ET LIMITES
    // ========================================
    echo "<div class='section'><h2>⚡ Tests de performance</h2>";

    $start = microtime(true);

    // Test de création en masse
    echo "<h3>Tests de charge</h3>";

    $startBulk = microtime(true);
    for ($i = 1; $i <= 10; $i++) {
        $article = new ArticleEntity();
        $article->setTitre("Article en masse $i")
            ->setContenu("Contenu de test pour l'article $i")
            ->setVisibilite(ArticleEntity::VISIBILITE_PUBLIC)
            ->setEtat(ArticleEntity::ETAT_EN_ATTENTE)
            ->setUtilisateurId($utilisateurs['redacteur1']->getId());
        $articleDao->save($article);
    }
    $endBulk = microtime(true);
    $tempsBulk = round(($endBulk - $startBulk) * 1000, 2);
    afficherResultat("Création 10 articles en masse", true, "Temps: {$tempsBulk}ms");

    // Test de recherche avec gros volume
    $startSearch = microtime(true);
    $allArticlesNow = $articleDao->findAll();
    $endSearch = microtime(true);
    $tempsSearch = round(($endSearch - $startSearch) * 1000, 2);
    afficherResultat(
        "Recherche tous articles (gros volume)",
        true,
        "Trouvés: " . count($allArticlesNow) . " en {$tempsSearch}ms"
    );

    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'><h2>❌ Erreur critique</h2>";
    echo "<p>Message: " . $e->getMessage() . "</p>";
    echo "<p>Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
} finally {
    // ========================================
    // RÉSUMÉ FINAL
    // ========================================
    $tempsTotal = round((microtime(true) - $start) * 1000, 2);

    echo "<div class='section'><h2>📊 Résumé des tests</h2>";
    echo "<div class='info'>";
    echo "<h3>Statistiques finales</h3>";
    echo "<p><strong>Tests exécutés:</strong> $testsExecutes</p>";
    echo "<p><strong>Tests réussis:</strong> <span class='success'>$testsReussis</span></p>";
    echo "<p><strong>Tests échoués:</strong> <span class='error'>$testsEchecs</span></p>";

    $pourcentageReussite = $testsExecutes > 0 ? round(($testsReussis / $testsExecutes) * 100, 2) : 0;
    echo "<p><strong>Taux de réussite:</strong> $pourcentageReussite%</p>";
    echo "<p><strong>Temps total d'exécution:</strong> {$tempsTotal}ms</p>";

    if ($pourcentageReussite >= 90) {
        echo "<h3 class='success'>🎉 Excellent ! Tes DAO fonctionnent parfaitement !</h3>";
    } elseif ($pourcentageReussite >= 70) {
        echo "<h3 class='info'>👍 Bon travail ! Quelques ajustements à faire.</h3>";
    } else {
        echo "<h3 class='error'>⚠️ Des améliorations sont nécessaires.</h3>";
    }

    echo "</div>";

    // Données créées pendant les tests
    echo "<h3>Données de test créées</h3>";
    echo "<ul>";
    echo "<li>Utilisateurs: " . count($utilisateurs) . "</li>";
    echo "<li>Articles: " . count($articles) . " + 10 en masse</li>";
    echo "<li>Commentaires: " . count($commentaires) . "</li>";
    echo "<li>Actions de modération: " . count($moderations) . "</li>";
    echo "</ul>";

    echo "<p class='info'><strong>Note:</strong> Ce test a créé de nombreuses données dans votre base. ";
    echo "Pensez à nettoyer la base de test après usage si nécessaire.</p>";

    echo "</div>";
}

echo "</body></html>";
?>