<?php

declare(strict_types=1);

namespace App\Core;

class Flash
{
    public static function success(string $message): void
    {
        self::message('success', $message);
    }

    public static function danger(string $message): void
    {
        self::message('danger', $message);
    }

    public static function message(string $type, string $message): void
    {
        $_SESSION['_flash'] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    public static function pullMessage(): ?array
    {
        if (empty($_SESSION['_flash']) || !is_array($_SESSION['_flash'])) {
            return null;
        }

        $flash = $_SESSION['_flash'];
        unset($_SESSION['_flash']);

        return $flash;
    }

    public static function withInput(array $input): void
    {
        unset(
            $input['password'],
            $input['password_confirm'],
            $input['current_password'],
            $input['_token']
        );
        $_SESSION['_old'] = $input;
    }

    public static function withErrors(array $errors): void
    {
        $_SESSION['_errors'] = $errors;
    }

    public static function errors(): array
    {
        return is_array($_SESSION['_errors'] ?? null) ? $_SESSION['_errors'] : [];
    }

    public static function clearFormState(): void
    {
        unset($_SESSION['_errors'], $_SESSION['_old']);
    }

    public static function hasOld(): bool
    {
        return is_array($_SESSION['_old'] ?? null);
    }

    public static function old(string $key, mixed $default = ''): mixed
    {
        $old = $_SESSION['_old'] ?? null;
        if (!is_array($old) || !array_key_exists($key, $old)) {
            return $default;
        }

        return $old[$key];
    }

    public static function hasError(string $key): bool
    {
        return isset(self::errors()[$key]);
    }

    public static function error(string $key): string
    {
        return (string) (self::errors()[$key] ?? '');
    }
}
