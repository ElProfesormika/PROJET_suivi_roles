<?php

declare(strict_types=1);

/**
 * Charge le registre historique (personne, trajet, accompagner, vehicule)
 * dans la base de l'application, avec un mot de passe pour chaque enseignant.
 *
 * Usage : php database/import_legacy.php
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Ce script s'execute en ligne de commande.\n");
    exit(1);
}

$config = require dirname(__DIR__) . '/config/config.php';
$db = $config['db'];
$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $db['host'],
    (int) $db['port'],
    $db['name'],
    $db['charset']
);

$pdo = new PDO($dsn, $db['user'], $db['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$sql = file_get_contents(__DIR__ . '/legacy_ml10172h.sql');
if ($sql === false) {
    fwrite(STDERR, "Fichier legacy_ml10172h.sql introuvable.\n");
    exit(1);
}

$pdo->exec('DROP TABLE IF EXISTS legacy_accompagner, legacy_trajet, legacy_vehicule, legacy_personne');

$pdo->exec(
    'CREATE TABLE legacy_personne (
        idPersonne INT NOT NULL,
        civilite VARCHAR(4) NULL,
        nom VARCHAR(50) NOT NULL,
        prenom VARCHAR(50) NOT NULL,
        mail VARCHAR(50) NOT NULL,
        PRIMARY KEY (idPersonne)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);
$pdo->exec(
    'CREATE TABLE legacy_vehicule (
        idVehicule INT NOT NULL,
        immatriculation VARCHAR(50) NOT NULL,
        marque VARCHAR(50) NOT NULL,
        type VARCHAR(50) NOT NULL,
        PRIMARY KEY (idVehicule)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);
$pdo->exec(
    'CREATE TABLE legacy_trajet (
        idTrajet INT NOT NULL,
        nature VARCHAR(50) NOT NULL,
        dateDepart DATE NOT NULL,
        heureDepart TIME NOT NULL,
        dateArrivee DATE NOT NULL,
        heureArrivee TIME NOT NULL,
        lieuDepart VARCHAR(50) NOT NULL,
        destination VARCHAR(50) NOT NULL,
        kilometrageDepart INT NULL,
        kilometrageRetour INT NULL,
        idPersonne INT NOT NULL,
        idVehicule INT NULL,
        PRIMARY KEY (idTrajet)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);
$pdo->exec(
    'CREATE TABLE legacy_accompagner (
        idTrajet INT NOT NULL,
        idPersonne INT NOT NULL,
        PRIMARY KEY (idTrajet, idPersonne)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$targets = [
    'personne' => 'legacy_personne',
    'vehicule' => 'legacy_vehicule',
    'trajet' => 'legacy_trajet',
    'accompagner' => 'legacy_accompagner',
];

foreach ($targets as $from => $to) {
    $needle = 'INSERT INTO `' . $from . '`';
    $offset = 0;
    $count = 0;
    while (($pos = strpos($sql, $needle, $offset)) !== false) {
        $end = strpos($sql, ";\n", $pos);
        if ($end === false) {
            throw new RuntimeException('INSERT incomplet pour ' . $from);
        }
        $statement = substr($sql, $pos, $end - $pos);
        $statement = preg_replace('/^INSERT INTO `' . preg_quote($from, '/') . '`/', 'INSERT INTO `' . $to . '`', $statement, 1);
        $pdo->exec($statement);
        $count++;
        $offset = $end + 1;
    }
    echo $from . ' : ' . $count . " ordre(s) SQL\n";
}

$password = 'Enseignant123!';
$hash = password_hash($password, PASSWORD_DEFAULT);

    $pdo->beginTransaction();

    try {
        $pdo->exec('DELETE FROM trajet');
    $typeIds = [];
    foreach (['Visite stagiaire' => 'visite stagiaire', 'Visite alternant' => 'visite alternant'] as $label => $unused) {
        $pdo->prepare('INSERT INTO types_missions (libelle, actif) VALUES (?, 1) ON DUPLICATE KEY UPDATE actif = 1')->execute([$label]);
        $stmt = $pdo->prepare('SELECT id FROM types_missions WHERE libelle = ?');
        $stmt->execute([$label]);
        $typeIds[$label] = (int) $stmt->fetchColumn();
    }
    $natureToType = [
        'visite stagiaire' => 'Visite stagiaire',
        'visite alternant' => 'Visite alternant',
    ];

    $vehicleMap = [];
    $vehicles = $pdo->query('SELECT * FROM legacy_vehicule')->fetchAll();
    $findVehicle = $pdo->prepare('SELECT idVehicule FROM vehicule WHERE immatriculation = ? AND marque = ? AND type = ? LIMIT 1');
    $insertVehicle = $pdo->prepare('INSERT INTO vehicule (immatriculation, marque, type) VALUES (?, ?, ?)');
    foreach ($vehicles as $vehicle) {
        $plate = trim((string) $vehicle['immatriculation']);
        $findVehicle->execute([$plate, $vehicle['marque'], $vehicle['type']]);
        $id = $findVehicle->fetchColumn();
        if ($id === false) {
            $insertVehicle->execute([$plate, $vehicle['marque'], $vehicle['type']]);
            $id = (int) $pdo->lastInsertId();
        }
        $vehicleMap[(int) $vehicle['idVehicule']] = (int) $id;
    }

    $userMap = [];
    $findUser = $pdo->prepare('SELECT idPersonne FROM personne WHERE mail = ? LIMIT 1');
    $insertUser = $pdo->prepare(
        'INSERT INTO personne (civilite, nom, prenom, mail, password_hash, role, departement, actif)
         VALUES (?, ?, ?, ?, ?, \'enseignant\', \'IUT de Roanne\', 1)'
    );
    $people = $pdo->query('SELECT * FROM legacy_personne ORDER BY idPersonne')->fetchAll();
    foreach ($people as $person) {
        $findUser->execute([$person['mail']]);
        $userId = $findUser->fetchColumn();
        if ($userId === false) {
            $civilite = $person['civilite'] !== '' ? $person['civilite'] : null;
            $insertUser->execute([$civilite, $person['nom'], $person['prenom'], $person['mail'], $hash]);
            $userId = (int) $pdo->lastInsertId();
        }
        $userMap[(int) $person['idPersonne']] = (int) $userId;
    }

    $validator = $pdo->query("SELECT idPersonne FROM personne WHERE role = 'responsable' AND actif = 1 ORDER BY idPersonne LIMIT 1")->fetchColumn();
    $validatorId = $validator === false ? null : (int) $validator;

    $insertMission = $pdo->prepare(
        'INSERT INTO trajet
            (idTrajet, nature, dateDepart, heureDepart, dateArrivee, heureArrivee,
             lieuDepart, destination, kilometrageDepart, kilometrageRetour, idPersonne, idVehicule,
             statut, validated_by, validated_at, commentaire_validation)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'validee\', ?, ?, ?)'
    );

    $trips = $pdo->query('SELECT * FROM legacy_trajet ORDER BY idTrajet')->fetchAll();
    $drivers = [];
    $imported = 0;
    foreach ($trips as $trip) {
        $personId = (int) $trip['idPersonne'];
        if (!isset($userMap[$personId], $natureToType[$trip['nature']])) {
            throw new RuntimeException('Trajet ' . $trip['idTrajet'] . ' sans conducteur ou nature.');
        }
        $drivers[(int) $trip['idTrajet']] = $personId;
        $vehicleId = $trip['idVehicule'] === null ? null : ($vehicleMap[(int) $trip['idVehicule']] ?? null);
        $kmDepart = $trip['kilometrageDepart'] === null ? null : (int) $trip['kilometrageDepart'];
        $kmRetour = $trip['kilometrageRetour'] === null ? null : (int) $trip['kilometrageRetour'];
        if (($kmDepart === null) !== ($kmRetour === null)) {
            $kmDepart = null;
            $kmRetour = null;
        }
        if ($kmDepart !== null && $kmRetour !== null && $kmRetour < $kmDepart) {
            $kmRetour = $kmDepart;
        }
        $insertMission->execute([
            (int) $trip['idTrajet'],
            $natureToType[$trip['nature']],
            $trip['dateDepart'],
            $trip['heureDepart'],
            $trip['dateArrivee'],
            $trip['heureArrivee'],
            $trip['lieuDepart'],
            $trip['destination'],
            $kmDepart,
            $kmRetour,
            $userMap[$personId],
            $vehicleId,
            $validatorId,
            $trip['dateArrivee'] . ' ' . $trip['heureArrivee'],
            'Reprise du registre historique.',
        ]);
        $imported++;
    }

    $insertLink = $pdo->prepare(
        'INSERT IGNORE INTO accompagner (idTrajet, idPersonne) VALUES (?, ?)'
    );
    $links = 0;
    $rows = $pdo->query('SELECT idTrajet, idPersonne FROM legacy_accompagner')->fetchAll();
    foreach ($rows as $row) {
        $tripId = (int) $row['idTrajet'];
        $personId = (int) $row['idPersonne'];
        if (($drivers[$tripId] ?? null) === $personId) {
            continue;
        }
        if (!isset($userMap[$personId], $drivers[$tripId])) {
            continue;
        }
        $insertLink->execute([$tripId, $userMap[$personId]]);
        $links++;
    }

    $pdo->commit();
    $pdo->exec('ALTER TABLE trajet AUTO_INCREMENT = 1426');
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $pdo->exec('DROP TABLE IF EXISTS legacy_accompagner, legacy_trajet, legacy_vehicule, legacy_personne');
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}

$pdo->exec('DROP TABLE IF EXISTS legacy_accompagner, legacy_trajet, legacy_vehicule, legacy_personne');

echo "Enseignants : " . count($userMap) . "\n";
echo "Missions importees : {$imported}\n";
echo "Accompagnants lies : {$links}\n";
echo "Mot de passe initial des enseignants : {$password}\n";
