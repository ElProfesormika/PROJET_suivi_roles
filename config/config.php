<?php

declare(strict_types=1);

/**
 * Configuration de l'application.
 *
 * En local sous Linux (LAMP), l'utilisateur MySQL est mission_ujm.
 * Sous XAMPP, on utilise souvent root sans mot de passe.
 *
 * Sur InfinityFree, remplacer host, name, user et pass par les
 * parametres fournis dans le panneau (souvent un hote du type
 * sql123.infinityfree.com, et non localhost).
 *
 * Les secrets d'hebergement peuvent aussi etre places dans
 * config/config.local.php (fichier ignore par Git).
 */
return [
    'app' => [
        'name' => 'Missions IUT Roanne',
        'debug' => true,
    ],
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'mission_ujm',
        'user' => 'mission_ujm',
        'pass' => '3b449e36f7ffe63c55478138',
        'charset' => 'utf8mb4',
    ],
];
