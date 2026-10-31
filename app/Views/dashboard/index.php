<div class="page-head">
    <div>
        <p class="eyebrow">IUT de Roanne</p>
        <h1>Tableau de bord</h1>
        <p class="text-muted"><?= e($scopeLabel) ?></p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/missions/create')) ?>">Nouvelle mission</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <p class="stat-label">Missions</p>
                <p class="stat-value"><?= (int) $summary['total'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card tone-navy h-100">
            <div class="card-body">
                <p class="stat-label">Kilométrage total</p>
                <p class="stat-value"><?= e(formatKm($summary['km_total'])) ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card tone-gold h-100">
            <div class="card-body">
                <p class="stat-label">Distance moyenne</p>
                <p class="stat-value"><?= e(formatKm($summary['km_moyen'], 1)) ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card tone-burgundy h-100">
            <div class="card-body">
                <p class="stat-label">En attente</p>
                <p class="stat-value"><?= (int) $summary['en_attente'] ?></p>
            </div>
        </div>
    </div>
</div>

<?php require dirname(__DIR__) . '/partials/charts.php'; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-white">Missions récentes</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Enseignant</th>
                        <th>Destination</th>
                        <th>Distance</th>
                        <th>Statut</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($recent === []): ?>
                        <tr><td colspan="5" class="empty-note">Aucune mission enregistrée.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recent as $mission): ?>
                            <tr>
                                <td><a href="<?= e(url('/missions/' . $mission['id'])) ?>"><?= e(formatDate($mission['date_mission'])) ?></a></td>
                                <td><?= e($mission['user_prenom'] . ' ' . $mission['user_nom']) ?></td>
                                <td><?= e($mission['destination']) ?></td>
                                <td><?= e(formatKm($mission['distance_km'])) ?></td>
                                <td><?= statutBadge($mission['statut']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white">Principales destinations</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                    <tr>
                        <th>Destination</th>
                        <th class="text-end">Missions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($destinations === []): ?>
                        <tr><td colspan="2" class="empty-note">Aucune destination.</td></tr>
                    <?php else: ?>
                        <?php foreach ($destinations as $row): ?>
                            <tr>
                                <td><?= e($row['destination']) ?></td>
                                <td class="text-end"><?= (int) $row['nb'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
