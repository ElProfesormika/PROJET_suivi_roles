<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

abstract class Model
{
    protected static function pdo(): PDO
    {
        return Database::pdo();
    }
}
