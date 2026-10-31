<?php

declare(strict_types=1);

define('APP_ROOT', __DIR__);

$config = require APP_ROOT . '/config/config.php';

$localConfig = APP_ROOT . '/config/config.local.php';
if (is_file($localConfig)) {
    $config = array_replace_recursive($config, require $localConfig);
}

define('APP_CONFIG', $config);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = APP_ROOT . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$scriptDir = rtrim($scriptDir, '/');
if ($scriptDir === '/' || $scriptDir === '.') {
    $scriptDir = '';
}

if (str_ends_with($scriptDir, '/public')) {
    $base = substr($scriptDir, 0, -strlen('/public'));
} else {
    $base = $scriptDir;
}

define('APP_BASE', $base);

if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    session_name('MISSIONUJM');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function url(string $path = ''): string
{
    $base = rtrim((string) APP_BASE, '/');
    $path = '/' . ltrim($path, '/');

    if ($path === '/') {
        return ($base === '' ? '' : $base) . '/';
    }

    return $base . $path;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function roleLabel(string $role): string
{
    return match ($role) {
        'admin' => 'Administrateur',
        'responsable' => 'Responsable',
        'enseignant' => 'Enseignant',
        default => $role,
    };
}

function statutLabel(string $statut): string
{
    return match ($statut) {
        'en_attente' => 'En attente',
        'validee' => 'Validée',
        'refusee' => 'Refusée',
        default => $statut,
    };
}

function statutBadge(string $statut): string
{
    $class = match ($statut) {
        'validee' => 'text-bg-success',
        'refusee' => 'text-bg-danger',
        default => 'text-bg-warning',
    };

    return '<span class="badge ' . $class . '">' . e(statutLabel($statut)) . '</span>';
}

function formatDate(?string $date): string
{
    if ($date === null || $date === '') {
        return '';
    }

    $dt = DateTimeImmutable::createFromFormat('Y-m-d', substr($date, 0, 10));
    return $dt instanceof DateTimeImmutable ? $dt->format('d/m/Y') : '';
}

function formatTime(?string $time): string
{
    if ($time === null || $time === '') {
        return '';
    }

    return substr($time, 0, 5);
}

function isVelo(?string $type): bool
{
    return $type !== null && strcasecmp(trim($type), 'Velo') === 0;
}

function formatKm(int|float|string|null $value, int $decimals = 0): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    return number_format((float) $value, $decimals, ',', ' ') . ' km';
}

function fieldClass(string $name): string
{
    return \App\Core\Flash::hasError($name) ? ' is-invalid' : '';
}

function fieldError(string $name): string
{
    if (!\App\Core\Flash::hasError($name)) {
        return '';
    }

    return '<div class="invalid-feedback d-block">' . e(\App\Core\Flash::error($name)) . '</div>';
}

function queryString(array $overrides = [], array $remove = []): string
{
    $params = $_GET;
    foreach ($remove as $key) {
        unset($params[$key]);
    }
    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }

    $built = http_build_query($params);

    return $built === '' ? '' : '?' . $built;
}

function selected($current, $expected): string
{
    return (string) $current === (string) $expected ? ' selected' : '';
}

function oldField(string $key, mixed $fallback = ''): mixed
{
    if (\App\Core\Flash::hasOld()) {
        return \App\Core\Flash::old($key, '');
    }

    return $fallback;
}
