<?php

use App\Core\Csrf;
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <h1 class="h3 mb-4">Changer mon mot de passe</h1>
        <form method="post" action="<?= e(url('/password')) ?>" class="card" novalidate>
            <div class="card-body">
                <?= Csrf::field() ?>
                <div class="mb-3">
                    <label class="form-label" for="current_password">Mot de passe actuel</label>
                    <input type="password" class="form-control<?= e(fieldClass('current_password')) ?>" id="current_password" name="current_password" autocomplete="current-password" required>
                    <?= fieldError('current_password') ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Nouveau mot de passe</label>
                    <input type="password" class="form-control<?= e(fieldClass('password')) ?>" id="password" name="password" autocomplete="new-password" required>
                    <div class="form-text">8 caractères minimum.</div>
                    <?= fieldError('password') ?>
                </div>
                <div class="mb-0">
                    <label class="form-label" for="password_confirm">Confirmation</label>
                    <input type="password" class="form-control<?= e(fieldClass('password_confirm')) ?>" id="password_confirm" name="password_confirm" autocomplete="new-password" required>
                    <?= fieldError('password_confirm') ?>
                </div>
            </div>
            <div class="card-footer bg-white">
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>
