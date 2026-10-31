<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Vehicule extends Model
{
    public static function all(bool $activeOnly = false): array
    {
        $sql = 'SELECT v.idVehicule AS id,
                    v.immatriculation,
                    v.marque,
                    v.type,
                    1 AS actif,
                    (SELECT MAX(t.kilometrageRetour)
                     FROM trajet t
                     WHERE t.idVehicule = v.idVehicule AND t.kilometrageRetour IS NOT NULL) AS dernier_km
                FROM vehicule v
                ORDER BY v.marque, v.type, v.idVehicule';

        return self::pdo()->query($sql)->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::pdo()->prepare(
            'SELECT idVehicule AS id, immatriculation, marque, type, 1 AS actif
             FROM vehicule
             WHERE idVehicule = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }
}
