<?php

use App\Core\Csrf;
use App\Core\Flash;

$account = is_array($user) ? $user : [];
$nom = (string) oldField('nom', $account['nom'] ?? '');
$prenom = (string) oldField('prenom', $account['prenom'] ?? '');
$email = (string) oldField('email', $account['email'] ?? '');
$role = (string) oldField('role', $account['role'] ?? 'enseignant');
$departement = (string) oldField('departement', $account['departement'] ?? '');
$telephone = (string) oldField('telephone', $account['telephone'] ?? '');

if (Flash::hasOld()) {
    $actif = (string) Flash::old('actif', '') === '1';
} else {
    $actif = $account === [] || (int) ($account['actif'] ?? 1) === 1;
}
?>
<form method="post" action="<?= e($formAction) ?>" class="card" novalidate>
    <div class="card-body">
        <?= Csrf::field() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="prenom">Prénom</label>
                <input type="text" class="form-control<?= e(fieldClass('prenom')) ?>" id="prenom" name="prenom" value="<?= e($prenom) ?>" maxlength="100" required>
                <?= fieldError('prenom') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="nom">Nom</label>
                <input type="text" class="form-control<?= e(fieldClass('nom')) ?>" id="nom" name="nom" value="<?= e($nom) ?>" maxlength="100" required>
                <?= fieldError('nom') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="email">Email</label>
                <input type="email" class="form-control<?= e(fieldClass('email')) ?>" id="email" name="email" value="<?= e($email) ?>" maxlength="190" required>
                <?= fieldError('email') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="role">Rôle</label>
                <select class="form-select<?= e(fieldClass('role')) ?>" id="role" name="role" required>
                    <option value="enseignant"<?= selected($role, 'enseignant') ?>>Enseignant</option>
                    <option value="responsable"<?= selected($role, 'responsable') ?>>Responsable</option>
                    <option value="admin"<?= selected($role, 'admin') ?>>Administrateur</option>
                </select>
                <?= fieldError('role') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="departement">Département</label>
                <input type="text" class="form-control<?= e(fieldClass('departement')) ?>" id="departement" name="departement" value="<?= e($departement) ?>" maxlength="120" placeholder="Ex. Informatique">
                <?= fieldError('departement') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="telephone">Téléphone</label>
                <input type="text" class="form-control<?= e(fieldClass('telephone')) ?>" id="telephone" name="telephone" value="<?= e($telephone) ?>" maxlength="30">
                <?= fieldError('telephone') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="password"><?= $passwordRequired ? 'Mot de passe' : 'Nouveau mot de passe' ?></label>
                <input type="password" class="form-control<?= e(fieldClass('password')) ?>" id="password" name="password" autocomplete="new-password" <?= $passwordRequired ? 'required' : '' ?>>
                <?php if (!$passwordRequired): ?>
                    <div class="form-text">Laissez vide pour conserver le mot de passe actuel.</div>
                <?php endif; ?>
                <?= fieldError('password') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="password_confirm">Confirmation</label>
                <input type="password" class="form-control<?= e(fieldClass('password_confirm')) ?>" id="password_confirm" name="password_confirm" autocomplete="new-password" <?= $passwordRequired ? 'required' : '' ?>>
                <?= fieldError('password_confirm') ?>
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="actif" name="actif" value="1"<?= $actif ? ' checked' : '' ?>>
                    <label class="form-check-label" for="actif">Compte actif</label>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white d-flex gap-2">
        <button type="submit" class="btn btn-primary"><?= e($submitLabel) ?></button>
        <a class="btn btn-outline-secondary" href="<?= e(url('/users')) ?>">Annuler</a>
    </div>
</form>
