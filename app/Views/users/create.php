<div class="mb-4">
    <h1 class="h3 mb-1">Nouvel utilisateur</h1>
    <p class="text-muted mb-0">Le mot de passe est enregistré sous forme d'empreinte, jamais en clair.</p>
</div>
<?php
$formAction = url('/users/store');
$submitLabel = 'Créer le compte';
$passwordRequired = true;
require __DIR__ . '/_form.php';
