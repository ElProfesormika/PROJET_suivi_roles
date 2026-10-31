<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

class Auth
{
    private static bool $resolved = false;
    private static ?array $cache = null;

    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$cache;
        }

        self::$resolved = true;
        $sessionUser = $_SESSION['user'] ?? null;

        if (!is_array($sessionUser) || empty($sessionUser['id'])) {
            self::$cache = null;
            return null;
        }

        $fresh = User::find((int) $sessionUser['id']);
        if ($fresh === null || (int) $fresh['actif'] !== 1) {
            unset($_SESSION['user']);
            self::$cache = null;
            return null;
        }

        $_SESSION['user'] = self::payload($fresh);
        self::$cache = $_SESSION['user'];

        return self::$cache;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): int
    {
        return (int) (self::user()['id'] ?? 0);
    }

    public static function role(): string
    {
        return (string) (self::user()['role'] ?? '');
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    public static function managesMissions(): bool
    {
        return in_array(self::role(), ['admin', 'responsable'], true);
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = self::payload($user);
        self::$resolved = true;
        self::$cache = $_SESSION['user'];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        self::$resolved = true;
        self::$cache = null;

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
        }

        session_destroy();
    }

    private static function payload(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'nom' => (string) $user['nom'],
            'prenom' => (string) $user['prenom'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
            'departement' => $user['departement'],
        ];
    }
}
