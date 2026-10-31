<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\MissionController;
use App\Controllers\StatsController;
use App\Controllers\UserController;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Router;

require dirname(__DIR__) . '/bootstrap.php';

if (APP_CONFIG['app']['debug'] ?? false) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

set_exception_handler(static function (Throwable $exception): void {
    http_response_code(500);
    if (APP_CONFIG['app']['debug'] ?? false) {
        echo '<pre>' . htmlspecialchars(
            $exception->getMessage() . "\n" . $exception->getTraceAsString(),
            ENT_QUOTES,
            'UTF-8'
        ) . '</pre>';
        return;
    }

    echo 'Une erreur interne est survenue.';
});

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($method === 'POST' && !Csrf::verify(isset($_POST['_token']) ? (string) $_POST['_token'] : null)) {
    http_response_code(419);
    (new Controller())->render('errors/419', [
        'title' => 'Jeton de sécurité invalide',
    ]);
    exit;
}

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = is_string($uri) ? $uri : '/';

if (APP_BASE !== '' && str_starts_with($uri, APP_BASE)) {
    $uri = substr($uri, strlen(APP_BASE));
}

$uri = '/' . trim($uri, '/');
if ($uri === '/public') {
    $uri = '/';
} elseif (str_starts_with($uri, '/public/')) {
    $uri = substr($uri, strlen('/public')) ?: '/';
}

if ($uri !== '/') {
    $uri = rtrim($uri, '/') ?: '/';
}

$router = new Router();
$router->get('/', [AuthController::class, 'home']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/dashboard', [DashboardController::class, 'index']);

$router->get('/missions', [MissionController::class, 'index']);
$router->get('/missions/create', [MissionController::class, 'create']);
$router->get('/missions/export', [MissionController::class, 'export']);
$router->post('/missions/store', [MissionController::class, 'store']);
$router->get('/missions/{id}', [MissionController::class, 'show']);
$router->get('/missions/{id}/edit', [MissionController::class, 'edit']);
$router->post('/missions/{id}/update', [MissionController::class, 'update']);
$router->post('/missions/{id}/delete', [MissionController::class, 'destroy']);
$router->post('/missions/{id}/status', [MissionController::class, 'status']);

$router->get('/users', [UserController::class, 'index']);
$router->get('/users/create', [UserController::class, 'create']);
$router->post('/users/store', [UserController::class, 'store']);
$router->get('/users/{id}/edit', [UserController::class, 'edit']);
$router->post('/users/{id}/update', [UserController::class, 'update']);
$router->post('/users/{id}/delete', [UserController::class, 'destroy']);

$router->get('/password', [UserController::class, 'passwordForm']);
$router->post('/password', [UserController::class, 'passwordUpdate']);

$router->get('/stats', [StatsController::class, 'index']);

$router->dispatch($method, $uri);
