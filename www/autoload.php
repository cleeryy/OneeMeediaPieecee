<?php
// Définir une constante pour éviter les inclusions multiples
define('ONEMEDIAPIECE_VERSION', '1.0.0');

/**
 * Fonction d'autoloading personnalisée
 * @param string $class Nom complet de la classe avec namespace
 */
spl_autoload_register(function ($class) {
    // Convertir les namespaces en structure de dossiers
    // App\Core\Database devient src/Core/Database.php

    // Remplacer le namespace principal par le dossier source
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/src/';

    // Si la classe ne commence pas par notre namespace, ne rien faire
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Obtenir le nom relatif de la classe
    $relative_class = substr($class, $len);

    // Remplacer les namespace separators \ par des directory separators /
    // Ajouter .php à la fin
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Si le fichier existe, le charger
    if (file_exists($file)) {
        require $file;
    }
});
