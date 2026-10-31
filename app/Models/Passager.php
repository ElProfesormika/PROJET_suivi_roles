<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Passager extends Model
{
    public static function forMission(int $missionId): array
    {
        $stmt = self::pdo()->prepare(
            'SELECT p.idPersonne AS id, p.nom, p.prenom
             FROM personne p
             INNER JOIN accompagner a ON a.idPersonne = p.idPersonne
             WHERE a.idTrajet = ?
             ORDER BY p.nom, p.prenom'
        );
        $stmt->execute([$missionId]);

        return $stmt->fetchAll();
    }

    public static function sync(int $missionId, array $people, int $driverId): void
    {
        $pdo = self::pdo();
        $delete = $pdo->prepare('DELETE FROM accompagner WHERE idTrajet = ?');
        $delete->execute([$missionId]);

        $find = $pdo->prepare('SELECT idPersonne FROM personne WHERE nom = ? AND prenom = ? LIMIT 1');
        $link = $pdo->prepare('INSERT INTO accompagner (idTrajet, idPersonne) VALUES (?, ?)');

        $seen = [];
        foreach ($people as $person) {
            $nom = trim((string) ($person['nom'] ?? ''));
            $prenom = trim((string) ($person['prenom'] ?? ''));
            if ($nom === '' && $prenom === '') {
                continue;
            }

            $key = mb_strtolower($nom . '|' . $prenom, 'UTF-8');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $find->execute([$nom, $prenom]);
            $id = $find->fetchColumn();
            if ($id === false || (int) $id === $driverId) {
                continue;
            }

            $link->execute([$missionId, (int) $id]);
        }
    }
}
