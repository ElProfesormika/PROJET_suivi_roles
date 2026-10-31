<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $db = APP_CONFIG['db'];
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $db['host'],
            (int) $db['port'],
            $db['name'],
            $db['charset']
        );

        try {
            self::$pdo = new PDO($dsn, $db['user'], $db['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            self::fail($exception);
        }

        return self::$pdo;
    }

    private static function fail(PDOException $exception): void
    {
        http_response_code(500);
        $debug = (bool) (APP_CONFIG['app']['debug'] ?? false);

        echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">';
        echo '<title>Base de données indisponible</title>';
        echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">';
        echo '</head><body class="bg-light"><main class="container py-5">';
        echo '<div class="alert alert-danger">';
        echo '<h1 class="h4">Connexion à la base impossible</h1>';
        echo '<p class="mb-0">Vérifiez que MySQL est démarré, que la base <code>mission_ujm</code> existe, ';
        echo 'que <code>database/database.sql</code> a été importé, et que <code>config/config.php</code> contient les bons identifiants.</p>';
        echo '</div>';

        if ($debug) {
            echo '<pre class="small text-danger">' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
        }

        echo '</main></body></html>';
        exit;
    }
}
