<div class="page-head">
    <div>
        <p class="eyebrow">Registre</p>
        <h1>Nouvelle mission</h1>
        <p class="text-muted mb-0">La mission sera enregistrée avec le statut « en attente ».</p>
    </div>
</div>
<?php
$formAction = url('/missions/store');
$submitLabel = 'Enregistrer la mission';
require __DIR__ . '/_form.php';
