<div class="page-head">
    <div>
        <p class="eyebrow">IUT de Roanne</p>
        <h1>Statistiques</h1>
        <p class="text-muted"><?= e($scopeLabel) ?></p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-4">
        <div class="card stat-card h-100"><div class="card-body"><p class="stat-label">Nombre total de missions</p><p class="stat-value"><?= (int) $summary['total'] ?></p></div></div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="card stat-card tone-navy h-100"><div class="card-body"><p class="stat-label">Kilométrage total</p><p class="stat-value"><?= e(formatKm($summary['km_total'])) ?></p></div></div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="card stat-card tone-gold h-100"><div class="card-body"><p class="stat-label">Distance moyenne</p><p class="stat-value"><?= e(formatKm($summary['km_moyen'], 1)) ?></p></div></div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="card stat-card tone-burgundy h-100"><div class="card-body"><p class="stat-label">Missions en attente</p><p class="stat-value"><?= (int) $summary['en_attente'] ?></p></div></div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="card stat-card h-100"><div class="card-body"><p class="stat-label">Missions validées</p><p class="stat-value"><?= (int) $summary['validees'] ?></p></div></div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="card stat-card tone-burgundy h-100"><div class="card-body"><p class="stat-label">Missions refusées</p><p class="stat-value"><?= (int) $summary['refusees'] ?></p></div></div>
    </div>
</div>

<?php require dirname(__DIR__) . '/partials/charts.php'; ?>

<div class="card mb-4">
    <div class="card-header bg-white">Destinations les plus fréquentes</div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
            <tr>
                <th>Destination</th>
                <th class="text-end">Missions</th>
                <th class="text-end">Kilométrage</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($destinations === []): ?>
                <tr><td colspan="3" class="empty-note">Aucune donnée pour le moment.</td></tr>
            <?php else: ?>
                <?php foreach ($destinations as $row): ?>
                    <tr>
                        <td><?= e($row['destination']) ?></td>
                        <td class="text-end"><?= (int) $row['nb'] ?></td>
                        <td class="text-end"><?= e(formatKm($row['km'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($global): ?>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header bg-white">Par enseignant</div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                        <tr>
                            <th>Enseignant</th>
                            <th>Département</th>
                            <th class="text-end">Missions</th>
                            <th class="text-end">Kilométrage</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($teachers === []): ?>
                            <tr><td colspan="4" class="empty-note">Aucune mission.</td></tr>
                        <?php else: ?>
                            <?php foreach ($teachers as $row): ?>
                                <tr>
                                    <td><?= e($row['enseignant']) ?></td>
                                    <td><?= e($row['departement']) ?></td>
                                    <td class="text-end"><?= (int) $row['nb'] ?></td>
                                    <td class="text-end"><?= e(formatKm($row['km'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header bg-white">Par département</div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                        <tr>
                            <th>Département</th>
                            <th class="text-end">Missions</th>
                            <th class="text-end">Kilométrage</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($departments === []): ?>
                            <tr><td colspan="3" class="empty-note">Aucune mission.</td></tr>
                        <?php else: ?>
                            <?php foreach ($departments as $row): ?>
                                <tr>
                                    <td><?= e($row['departement']) ?></td>
                                    <td class="text-end"><?= (int) $row['nb'] ?></td>
                                    <td class="text-end"><?= e(formatKm($row['km'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
