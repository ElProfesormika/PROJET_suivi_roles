<div class="mb-4">
    <h1 class="h3 mb-1">Modifier l'utilisateur</h1>
    <p class="text-muted mb-0"><?= e($user['prenom'] . ' ' . $user['nom']) ?></p>
</div>
<?php
$formAction = url('/users/' . $user['id'] . '/update');
$submitLabel = 'Enregistrer';
$passwordRequired = false;
require __DIR__ . '/_form.php';
