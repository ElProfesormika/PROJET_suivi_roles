<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class TypeMission extends Model
{
    public static function all(bool $onlyActive = false): array
    {
        $sql = 'SELECT id, libelle, actif FROM types_missions';
        if ($onlyActive) {
            $sql .= ' WHERE actif = 1';
        }
        $sql .= ' ORDER BY libelle';

        return self::pdo()->query($sql)->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::pdo()->prepare(
            'SELECT id, libelle, actif FROM types_missions WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $type = $stmt->fetch();

        return $type === false ? null : $type;
    }
}
