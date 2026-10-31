<?php

use App\Core\Auth;
use App\Core\Csrf;

$canManage = Auth::managesMissions();
$isOwner = (int) $mission['user_id'] === Auth::id();
$canEdit = $canManage || ($isOwner && $mission['statut'] !== 'validee');
?>
<div class="page-head no-print">
    <div>
        <p class="eyebrow">Registre</p>
        <h1>Mission du <?= e(formatDate($mission['date_mission'])) ?></h1>
        <p class="mb-0"><?= statutBadge($mission['statut']) ?></p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">Imprimer</button>
        <?php if ($canEdit): ?>
            <a class="btn btn-primary" href="<?= e(url('/missions/' . $mission['id'] . '/edit')) ?>">Modifier</a>
        <?php endif; ?>
        <a class="btn btn-outline-secondary" href="<?= e(url('/missions')) ?>">Retour à la liste</a>
    </div>
</div>

<article class="card fiche-mission">
    <div class="card-body">
        <header class="fiche-head mb-4">
            <div>
                <p class="eyebrow mb-1">Université Jean Monnet</p>
                <h2 class="h4 mb-0">Registre de mission</h2>
            </div>
            <img class="fiche-logo" src="<?= e(url('img/logo-iut-roanne.png')) ?>" alt="IUT de Roanne, Université Jean Monnet">
        </header>

        <dl class="row mb-0">
            <dt class="col-sm-4">Départ</dt>
            <dd class="col-sm-8"><?= e(formatDate($mission['date_mission'])) ?> à <?= e(formatTime($mission['heure_depart'])) ?></dd>

            <dt class="col-sm-4">Arrivée</dt>
            <dd class="col-sm-8"><?= e(formatDate($mission['date_arrivee'] ?? $mission['date_mission'])) ?> à <?= e(formatTime($mission['heure_arrivee'])) ?></dd>

            <dt class="col-sm-4">Nature de la mission</dt>
            <dd class="col-sm-8"><?= e($mission['type_libelle']) ?></dd>

            <dt class="col-sm-4">Véhicule</dt>
            <dd class="col-sm-8">
                <?php if (empty($mission['vehicule_marque'])): ?>
                    Non renseigné
                <?php else: ?>
                    <?= e($mission['vehicule_marque'] . ' ' . $mission['vehicule_type']) ?>
                    <?php if (!empty($mission['vehicule_immatriculation'])): ?>
                        <span class="text-muted">(<?= e($mission['vehicule_immatriculation']) ?>)</span>
                    <?php endif; ?>
                <?php endif; ?>
            </dd>

            <dt class="col-sm-4">Conducteur</dt>
            <dd class="col-sm-8">
                <?= e($mission['user_prenom'] . ' ' . $mission['user_nom']) ?>
                <?php if (!empty($mission['departement'])): ?>
                    <span class="text-muted">(<?= e($mission['departement']) ?>)</span>
                <?php endif; ?>
            </dd>

            <dt class="col-sm-4">Personnes transportées</dt>
            <dd class="col-sm-8">
                <?php if ($passagers === []): ?>
                    Aucune
                <?php else: ?>
                    <ul class="mb-0">
                        <?php foreach ($passagers as $passager): ?>
                            <li><?= e($passager['prenom'] . ' ' . $passager['nom']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </dd>

            <dt class="col-sm-4">Lieu de départ</dt>
            <dd class="col-sm-8"><?= e($mission['lieu_depart']) ?></dd>

            <dt class="col-sm-4">Destination</dt>
            <dd class="col-sm-8"><?= e($mission['destination']) ?></dd>

            <dt class="col-sm-4">Kilométrage à la prise</dt>
            <dd class="col-sm-8"><?= $mission['km_depart'] === null ? 'Non relevé' : e(number_format((float) $mission['km_depart'], 0, ',', ' ')) ?></dd>

            <dt class="col-sm-4">Kilométrage à la restitution</dt>
            <dd class="col-sm-8"><?= $mission['km_retour'] === null ? 'Non relevé' : e(number_format((float) $mission['km_retour'], 0, ',', ' ')) ?></dd>

            <dt class="col-sm-4">Distance</dt>
            <dd class="col-sm-8 fw-semibold"><?= $mission['distance_km'] === null ? 'Sans compteur' : e(formatKm($mission['distance_km'])) ?></dd>

            <dt class="col-sm-4">Remarques</dt>
            <dd class="col-sm-8"><?= $mission['remarques'] !== null && $mission['remarques'] !== '' ? nl2br(e($mission['remarques'])) : 'Aucune' ?></dd>

            <dt class="col-sm-4">Statut</dt>
            <dd class="col-sm-8">
                <?= statutBadge($mission['statut']) ?>
                <?php if (!empty($mission['validated_at']) && !empty($mission['validateur_prenom'])): ?>
                    <span class="text-muted">
                        le <?= e(formatDate($mission['validated_at'])) ?>
                        par <?= e($mission['validateur_prenom'] . ' ' . $mission['validateur_nom']) ?>
                    </span>
                <?php endif; ?>
            </dd>

            <?php if (!empty($mission['commentaire_validation'])): ?>
                <dt class="col-sm-4">Commentaire de validation</dt>
                <dd class="col-sm-8"><?= nl2br(e($mission['commentaire_validation'])) ?></dd>
            <?php endif; ?>
        </dl>
    </div>
</article>

<?php if ($canManage): ?>
    <section class="card mt-4 no-print">
        <div class="card-body">
            <h2 class="h5">Validation</h2>
            <p class="text-muted">Le commentaire est obligatoire pour un refus. Il reste visible sur la fiche.</p>
            <form method="post" action="<?= e(url('/missions/' . $mission['id'] . '/status')) ?>">
                <?= Csrf::field() ?>
                <label class="form-label" for="commentaire">Commentaire</label>
                <textarea class="form-control mb-3" id="commentaire" name="commentaire" rows="3" maxlength="1000" placeholder="Précision pour l'enseignant, obligatoire en cas de refus"></textarea>
                <div class="d-flex flex-wrap gap-2">
                    <?php if ($mission['statut'] !== 'validee'): ?>
                        <button type="submit" class="btn btn-success" name="statut" value="validee">Valider la mission</button>
                    <?php endif; ?>
                    <?php if ($mission['statut'] !== 'refusee'): ?>
                        <button type="submit" class="btn btn-outline-danger" name="statut" value="refusee" onclick="return confirm('Refuser cette mission ?');">Refuser la mission</button>
                    <?php endif; ?>
                    <?php if ($mission['statut'] !== 'en_attente'): ?>
                        <button type="submit" class="btn btn-outline-secondary" name="statut" value="en_attente">Remettre en attente</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </section>

    <form method="post" action="<?= e(url('/missions/' . $mission['id'] . '/delete')) ?>" class="mt-3 no-print" onsubmit="return confirm('Supprimer définitivement cette mission ?');">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn-danger">Supprimer la mission</button>
    </form>
<?php endif; ?>
