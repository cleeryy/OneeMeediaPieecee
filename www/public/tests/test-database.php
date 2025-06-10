<?php
// test_database.php
require_once __DIR__ . '/../autoload.php';

use App\Core\Database;

try {
    $db = Database::getInstance();
    echo "Connexion à la base de données réussie !";

    // Tester une requête simple
    $result = $db->query("SELECT 1");
    var_dump($result);
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}

?>