<?php

use App\Core\Auth;
?>
<div class="page-head">
    <div>
        <p class="eyebrow">Registre</p>
        <h1>Missions</h1>
        <p class="text-muted mb-0">
            <?= Auth::managesMissions() ? 'Toutes les missions' : 'Vos missions' ?>
            · <?= (int) $total ?> résultat<?= (int) $total > 1 ? 's' : '' ?>
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="<?= e(url('/missions/export') . queryString([], ['page'])) ?>">Exporter en CSV</a>
        <a class="btn btn-primary" href="<?= e(url('/missions/create')) ?>">Nouvelle mission</a>
    </div>
</div>

<form method="get" action="<?= e(url('/missions')) ?>" class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6 col-xl-3">
                <label for="q" class="form-label">Recherche</label>
                <input type="search" class="form-control" id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="Destination, départ, enseignant">
            </div>
            <?php if (Auth::managesMissions()): ?>
                <div class="col-md-6 col-xl-3">
                    <label for="user_id" class="form-label">Enseignant</label>
                    <select class="form-select" id="user_id" name="user_id">
                        <option value="">Tous</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= (int) $user['id'] ?>"<?= selected($filters['user_id'], $user['id']) ?>>
                                <?= e($user['prenom'] . ' ' . $user['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="col-md-4 col-xl-2">
                <label for="statut" class="form-label">Statut</label>
                <select class="form-select" id="statut" name="statut">
                    <option value="">Tous</option>
                    <option value="en_attente"<?= selected($filters['statut'], 'en_attente') ?>>En attente</option>
                    <option value="validee"<?= selected($filters['statut'], 'validee') ?>>Validée</option>
                    <option value="refusee"<?= selected($filters['statut'], 'refusee') ?>>Refusée</option>
                </select>
            </div>
            <div class="col-md-4 col-xl-2">
                <label for="date_debut" class="form-label">Date de début</label>
                <input type="date" class="form-control" id="date_debut" name="date_debut" value="<?= e($filters['date_debut']) ?>">
            </div>
            <div class="col-md-4 col-xl-2">
                <label for="date_fin" class="form-label">Date de fin</label>
                <input type="date" class="form-control" id="date_fin" name="date_fin" value="<?= e($filters['date_fin']) ?>">
            </div>
        </div>
        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Filtrer</button>
            <a class="btn btn-outline-secondary" href="<?= e(url('/missions')) ?>">Réinitialiser</a>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
            <tr>
                <th>Date</th>
                <th>Enseignant</th>
                <th>Nature</th>
                <th>Trajet</th>
                <th>Horaires</th>
                <th>Distance</th>
                <th>Statut</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($missions === []): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">Aucune mission ne correspond à ces critères.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($missions as $mission): ?>
                    <tr>
                        <td><?= e(formatDate($mission['date_mission'])) ?></td>
                        <td><?= e($mission['user_prenom'] . ' ' . $mission['user_nom']) ?></td>
                        <td><?= e($mission['type_libelle']) ?></td>
                        <td>
                            <?= e($mission['lieu_depart']) ?> → <?= e($mission['destination']) ?>
                            <?php if (!empty($mission['vehicule_marque'])): ?>
                                <div class="small text-muted">
                                    <?= e($mission['vehicule_marque'] . ' ' . $mission['vehicule_type']) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?= e(formatTime($mission['heure_depart'])) ?> - <?= e(formatTime($mission['heure_arrivee'])) ?></td>
                        <td><?= $mission['distance_km'] === null ? '—' : e(formatKm($mission['distance_km'])) ?></td>
                        <td><?= statutBadge($mission['statut']) ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/missions/' . $mission['id'])) ?>">Voir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pages > 1): ?>
        <div class="card-footer bg-white">
            <nav aria-label="Pagination des missions">
                <ul class="pagination mb-0">
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <li class="page-item<?= $i === (int) $page ? ' active' : '' ?>">
                            <a class="page-link" href="<?= e(url('/missions') . queryString(['page' => $i === 1 ? null : $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>
