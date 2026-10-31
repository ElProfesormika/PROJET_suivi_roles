<?php

use App\Core\Csrf;
use App\Core\Flash;

$flash = Flash::pullMessage();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion - IUT de Roanne</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(url('css/app.css')) ?>" rel="stylesheet">
</head>
<body class="login-body">
<div class="login-shell">
    <section class="login-aside">
        <img src="<?= e(url('img/logo-iut-roanne.png')) ?>" alt="IUT de Roanne, Université Jean Monnet">
        <h1>Gestion des missions</h1>
        <p class="intro">Le registre des déplacements du personnel enseignant, centralisé pour l'IUT de Roanne.</p>
        <ul class="login-points">
            <li>Enregistrer une mission et les personnes transportées</li>
            <li>Calculer automatiquement la distance parcourue</li>
            <li>Faire valider le déplacement par le responsable</li>
            <li>Consulter les statistiques du service</li>
        </ul>
    </section>
    <section class="login-panel">
        <div class="panel-inner">
            <p class="eyebrow">Espace personnel</p>
            <h2 class="h3 mb-4">Connexion</h2>

            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
            <?php endif; ?>
            <?php if (Flash::hasError('form')): ?>
                <div class="alert alert-danger" role="alert"><?= e(Flash::error('form')) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('/login')) ?>" novalidate>
                <?= Csrf::field() ?>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control form-control-lg<?= e(fieldClass('email')) ?>" id="email" name="email" value="<?= e((string) oldField('email')) ?>" autocomplete="username" required autofocus>
                    <?= fieldError('email') ?>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control form-control-lg<?= e(fieldClass('password')) ?>" id="password" name="password" autocomplete="current-password" required>
                    <?= fieldError('password') ?>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100">Se connecter</button>
            </form>
        </div>
    </section>
</div>
</body>
</html>
<?php Flash::clearFormState(); ?>
