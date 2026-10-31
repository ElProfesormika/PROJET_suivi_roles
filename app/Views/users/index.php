<?php

use App\Core\Auth;
use App\Core\Csrf;
?>
<div class="page-head">
    <div>
        <p class="eyebrow">Administration</p>
        <h1>Utilisateurs</h1>
        <p class="text-muted mb-0">Comptes enseignant, responsable et administrateur.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/users/create')) ?>">Nouvel utilisateur</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
            <tr>
                <th>Nom</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Département</th>
                <th>État</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $account): ?>
                <tr>
                    <td><?= e($account['prenom'] . ' ' . $account['nom']) ?></td>
                    <td><?= e($account['email']) ?></td>
                    <td><?= e(roleLabel($account['role'])) ?></td>
                    <td><?= e((string) ($account['departement'] ?? '')) ?></td>
                    <td>
                        <?php if ((int) $account['actif'] === 1): ?>
                            <span class="badge text-bg-success">Actif</span>
                        <?php else: ?>
                            <span class="badge text-bg-secondary">Inactif</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/users/' . $account['id'] . '/edit')) ?>">Modifier</a>
                        <?php if ((int) $account['actif'] === 1 && (int) $account['id'] !== Auth::id()): ?>
                            <form method="post" action="<?= e(url('/users/' . $account['id'] . '/delete')) ?>" class="d-inline" onsubmit="return confirm('Désactiver ce compte ?');">
                                <?= Csrf::field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger">Désactiver</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
