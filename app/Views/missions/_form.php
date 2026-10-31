<?php

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;

$missionData = is_array($mission) ? $mission : [];
$selectedUser = (string) oldField('user_id', $missionData['user_id'] ?? Auth::id());
$selectedType = (string) oldField('type_mission_id', $missionData['type_mission_id'] ?? '');
$dateValue = (string) oldField('date_mission', isset($missionData['date_mission']) ? substr((string) $missionData['date_mission'], 0, 10) : '');
$heureDepart = (string) oldField('heure_depart', isset($missionData['heure_depart']) ? formatTime($missionData['heure_depart']) : '');
$heureArrivee = (string) oldField('heure_arrivee', isset($missionData['heure_arrivee']) ? formatTime($missionData['heure_arrivee']) : '');
$lieu = (string) oldField('lieu_depart', $missionData['lieu_depart'] ?? '');
$destination = (string) oldField('destination', $missionData['destination'] ?? '');
$selectedVehicle = (string) oldField('vehicule_id', $missionData['vehicule_id'] ?? '');
$dateArrivee = (string) oldField('date_arrivee', isset($missionData['date_arrivee']) ? substr((string) $missionData['date_arrivee'], 0, 10) : '');
$kmDepart = (string) oldField('km_depart', $missionData['km_depart'] ?? '');
$kmRetour = (string) oldField('km_retour', $missionData['km_retour'] ?? '');
$remarques = (string) oldField('remarques', $missionData['remarques'] ?? '');

if (Flash::hasOld()) {
    $rows = Flash::old('passagers', []);
    if (!is_array($rows)) {
        $rows = [];
    }
} else {
    $rows = $passagers;
}

if ($rows === []) {
    $rows = [['nom' => '', 'prenom' => '']];
}
?>
<form method="post" action="<?= e($formAction) ?>" class="card" novalidate>
    <div class="card-body">
        <?= Csrf::field() ?>
        <?php if (Flash::hasError('passagers')): ?>
            <div class="alert alert-danger"><?= e(Flash::error('passagers')) ?></div>
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="user_id">Enseignant</label>
                <?php if (Auth::role() === 'enseignant'): ?>
                    <input type="hidden" name="user_id" value="<?= (int) Auth::id() ?>">
                    <input type="text" class="form-control" id="user_id" value="<?= e(Auth::user()['prenom'] . ' ' . Auth::user()['nom']) ?>" disabled>
                <?php else: ?>
                    <select class="form-select<?= e(fieldClass('user_id')) ?>" id="user_id" name="user_id" required>
                        <option value="">Sélectionner</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= (int) $user['id'] ?>"<?= selected($selectedUser, $user['id']) ?>>
                                <?= e($user['prenom'] . ' ' . $user['nom']) ?> - <?= e(roleLabel($user['role'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= fieldError('user_id') ?>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="type_mission_id">Nature de la mission</label>
                <select class="form-select<?= e(fieldClass('type_mission_id')) ?>" id="type_mission_id" name="type_mission_id" required>
                    <option value="">Sélectionner</option>
                    <?php foreach ($types as $type): ?>
                        <?php
                        $isCurrent = $missionData !== [] && (int) ($missionData['type_mission_id'] ?? 0) === (int) $type['id'];
                        if ((int) $type['actif'] !== 1 && !$isCurrent) {
                            continue;
                        }
                        ?>
                        <option value="<?= (int) $type['id'] ?>"<?= selected($selectedType, $type['id']) ?>>
                            <?= e($type['libelle']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= fieldError('type_mission_id') ?>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="date_mission">Date de départ</label>
                <input type="date" class="form-control<?= e(fieldClass('date_mission')) ?>" id="date_mission" name="date_mission" value="<?= e($dateValue) ?>" required>
                <?= fieldError('date_mission') ?>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="date_arrivee">Date d'arrivée</label>
                <input type="date" class="form-control<?= e(fieldClass('date_arrivee')) ?>" id="date_arrivee" name="date_arrivee" value="<?= e($dateArrivee !== '' ? $dateArrivee : $dateValue) ?>" required>
                <?= fieldError('date_arrivee') ?>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="heure_depart">Heure de départ</label>
                <input type="time" class="form-control<?= e(fieldClass('heure_depart')) ?>" id="heure_depart" name="heure_depart" value="<?= e($heureDepart) ?>" required>
                <?= fieldError('heure_depart') ?>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="heure_arrivee">Heure d'arrivée</label>
                <input type="time" class="form-control<?= e(fieldClass('heure_arrivee')) ?>" id="heure_arrivee" name="heure_arrivee" value="<?= e($heureArrivee) ?>" required>
                <?= fieldError('heure_arrivee') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="lieu_depart">Lieu de départ</label>
                <input type="text" class="form-control<?= e(fieldClass('lieu_depart')) ?>" id="lieu_depart" name="lieu_depart" value="<?= e($lieu) ?>" maxlength="150" required>
                <?= fieldError('lieu_depart') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="destination">Destination</label>
                <input type="text" class="form-control<?= e(fieldClass('destination')) ?>" id="destination" name="destination" value="<?= e($destination) ?>" maxlength="150" required>
                <?= fieldError('destination') ?>
            </div>
            <div class="col-12">
                <label class="form-label" for="vehicule_id">Véhicule pris</label>
                <select class="form-select<?= e(fieldClass('vehicule_id')) ?>" id="vehicule_id" name="vehicule_id" required>
                    <option value="">Sélectionner</option>
                    <?php foreach ($vehicules as $vehicule): ?>
                        <?php
                        $isCurrentVehicle = $missionData !== [] && (int) ($missionData['vehicule_id'] ?? 0) === (int) $vehicule['id'];
                        if ((int) $vehicule['actif'] !== 1 && !$isCurrentVehicle) {
                            continue;
                        }
                        $label = $vehicule['marque'] . ' ' . $vehicule['type'];
                        if ($vehicule['immatriculation'] !== '') {
                            $label .= ' — ' . $vehicule['immatriculation'];
                        }
                        ?>
                        <option
                            value="<?= (int) $vehicule['id'] ?>"
                            data-type="<?= e($vehicule['type']) ?>"
                            data-km="<?= $vehicule['dernier_km'] === null ? '' : (int) $vehicule['dernier_km'] ?>"
                            <?= selected($selectedVehicle, $vehicule['id']) ?>
                        ><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= fieldError('vehicule_id') ?>
                <p id="km-hint" class="form-text mb-0">Le kilométrage est relevé à la prise du véhicule, puis à sa restitution.</p>
            </div>
            <div class="col-md-4" id="km-depart-wrap">
                <label class="form-label" for="km_depart">Kilométrage à la prise</label>
                <input type="number" class="form-control<?= e(fieldClass('km_depart')) ?>" id="km_depart" name="km_depart" value="<?= e($kmDepart) ?>" min="0" max="9999999" step="1">
                <?= fieldError('km_depart') ?>
            </div>
            <div class="col-md-4" id="km-retour-wrap">
                <label class="form-label" for="km_retour">Kilométrage à la restitution</label>
                <input type="number" class="form-control<?= e(fieldClass('km_retour')) ?>" id="km_retour" name="km_retour" value="<?= e($kmRetour) ?>" min="0" max="9999999" step="1">
                <?= fieldError('km_retour') ?>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <p id="distance-live" class="distance-live mb-2">Distance : --</p>
            </div>
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label mb-0">Autres personnes transportées</label>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-passager">Ajouter une personne</button>
                </div>
                <div id="passagers-list">
                    <?php foreach ($rows as $index => $row): ?>
                        <div class="row g-2 passager-row mb-2">
                            <div class="col-sm-5">
                                <input type="text" class="form-control" name="passagers[<?= (int) $index ?>][nom]" value="<?= e((string) ($row['nom'] ?? '')) ?>" placeholder="Nom" maxlength="100">
                            </div>
                            <div class="col-sm-5">
                                <input type="text" class="form-control" name="passagers[<?= (int) $index ?>][prenom]" value="<?= e((string) ($row['prenom'] ?? '')) ?>" placeholder="Prénom" maxlength="100">
                            </div>
                            <div class="col-sm-2">
                                <button type="button" class="btn btn-outline-secondary w-100 remove-passager">Retirer</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label" for="remarques">Remarques</label>
                <textarea class="form-control<?= e(fieldClass('remarques')) ?>" id="remarques" name="remarques" rows="4" maxlength="2000"><?= e($remarques) ?></textarea>
                <?= fieldError('remarques') ?>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white d-flex gap-2">
        <button type="submit" class="btn btn-primary"><?= e($submitLabel) ?></button>
        <a class="btn btn-outline-secondary" href="<?= e(url('/missions')) ?>">Annuler</a>
    </div>
</form>
