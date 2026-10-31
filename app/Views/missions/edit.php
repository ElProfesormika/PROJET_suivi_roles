<div class="page-head">
    <div>
        <p class="eyebrow">Registre</p>
        <h1>Modifier la mission</h1>
        <p class="text-muted mb-0">Mission du <?= e(formatDate($mission['date_mission'])) ?> vers <?= e($mission['destination']) ?>.</p>
    </div>
</div>
<?php
$formAction = url('/missions/' . $mission['id'] . '/update');
$submitLabel = 'Enregistrer les modifications';
require __DIR__ . '/_form.php';
