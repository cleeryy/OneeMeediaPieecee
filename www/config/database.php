<?php

return [
    'host' => '192.168.1.42',
    'port' => '3306',
    'dbname' => 'onemediapiece',
    'username' => 'root',
    'password' => 'SuperPassword!1',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // Force PDO a lancer des exceptions de type PDOException pour la DB
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   // Retourne des resultats sous forme de tableaux associatifs (cles nom de colonnes)
        PDO::ATTR_EMULATE_PREPARES => false                 // Force de vraies requetes et pas emuler -> type de donnees correct (et pas des strings), requetes et donnees separees, plus performant parfois!!
    ]
];