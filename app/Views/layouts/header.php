<?php

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;

$user = Auth::user();
$current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$current = is_string($current) ? $current : '/';
$logo = url('img/logo-iut-roanne.png');

$isActive = static function (string $path) use ($current): string {
    $now = rtrim($current, '/');
    $target = rtrim(url($path), '/');

    if ($path === '/missions/create') {
        return str_ends_with($now, '/missions/create') ? 'active' : '';
    }

    if ($path === '/missions') {
        if (str_ends_with($now, '/missions/create') || str_ends_with($now, '/missions/export')) {
            return '';
        }

        return ($now === $target || str_contains($now, '/missions/')) ? 'active' : '';
    }

    if ($path === '/dashboard') {
        return $now === $target ? 'active' : '';
    }

    return ($now === $target || str_starts_with($now, $target . '/')) ? 'active' : '';
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Missions') ?> - IUT de Roanne</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(url('css/app.css')) ?>" rel="stylesheet">
</head>
<body>
<header class="site-header no-print">
    <div class="brandbar">
        <div class="container d-flex align-items-center justify-content-between gap-3">
            <a class="brand" href="<?= e(url($user ? '/dashboard' : '/login')) ?>">
                <img src="<?= e($logo) ?>" alt="IUT de Roanne, Université Jean Monnet">
            </a>
            <?php if ($user): ?>
                <div class="brand-user d-none d-lg-flex">
                    <div class="text-end">
                        <strong><?= e($user['prenom'] . ' ' . $user['nom']) ?></strong>
                        <span><?= e(roleLabel($user['role'])) ?></span>
                    </div>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('/password')) ?>">Mot de passe</a>
                    <form method="post" action="<?= e(url('/logout')) ?>" class="m-0">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn-primary btn-sm">Déconnexion</button>
                    </form>
                </div>
                <button class="nav-toggle d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Ouvrir le menu">
                    Menu
                </button>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($user): ?>
        <nav class="navbar navbar-expand-lg site-nav p-0">
            <div class="container">
                <div class="collapse navbar-collapse" id="mainNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item"><a class="nav-link <?= e($isActive('/dashboard')) ?>" href="<?= e(url('/dashboard')) ?>">Tableau de bord</a></li>
                        <li class="nav-item"><a class="nav-link <?= e($isActive('/missions')) ?>" href="<?= e(url('/missions')) ?>">Missions</a></li>
                        <li class="nav-item"><a class="nav-link <?= e($isActive('/missions/create')) ?>" href="<?= e(url('/missions/create')) ?>">Nouvelle mission</a></li>
                        <li class="nav-item"><a class="nav-link <?= e($isActive('/stats')) ?>" href="<?= e(url('/stats')) ?>">Statistiques</a></li>
                        <?php if (Auth::isAdmin()): ?>
                            <li class="nav-item"><a class="nav-link <?= e($isActive('/users')) ?>" href="<?= e(url('/users')) ?>">Utilisateurs</a></li>
                        <?php endif; ?>
                    </ul>
                    <div class="d-lg-none text-white pb-3 d-flex flex-column gap-2">
                        <div><?= e($user['prenom'] . ' ' . $user['nom']) ?> · <?= e(roleLabel($user['role'])) ?></div>
                        <a class="btn btn-outline-light btn-sm" href="<?= e(url('/password')) ?>">Mot de passe</a>
                        <form method="post" action="<?= e(url('/logout')) ?>" class="m-0">
                            <?= Csrf::field() ?>
                            <button type="submit" class="btn btn-light btn-sm">Déconnexion</button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>
    <?php endif; ?>
</header>
<main class="container py-4">
    <?php $flash = Flash::pullMessage(); ?>
    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : 'success') ?> no-print" role="alert">
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>
