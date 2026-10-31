<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Models\Mission;
use App\Models\Passager;
use App\Models\TypeMission;
use App\Models\User;
use App\Models\Vehicule;
use Throwable;

class MissionController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $filters = $this->filtersFromRequest();
        $onlyUserId = Auth::managesMissions() ? null : Auth::id();
        $perPage = 10;
        $total = Mission::count($filters, $onlyUserId);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        if ($page > $pages) {
            $page = $pages;
        }

        $this->render('missions/index', [
            'title' => 'Missions',
            'missions' => Mission::search($filters, $onlyUserId, $perPage, ($page - 1) * $perPage),
            'filters' => $filters,
            'users' => Auth::managesMissions() ? User::active() : [],
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
        ]);
    }

    public function export(): void
    {
        $this->requireAuth();
        $filters = $this->filtersFromRequest();
        $onlyUserId = Auth::managesMissions() ? null : Auth::id();
        $rows = Mission::exportRows($filters, $onlyUserId);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="missions-iut-roanne.csv"');
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }

        fputcsv($out, [
            'Date de départ',
            'Date d\'arrivée',
            'Enseignant',
            'Département',
            'Nature',
            'Véhicule',
            'Type de véhicule',
            'Immatriculation',
            'Lieu de départ',
            'Destination',
            'Heure de départ',
            'Heure d\'arrivée',
            'Km à la prise',
            'Km à la restitution',
            'Distance (km)',
            'Personnes transportées',
            'Statut',
            'Remarques',
            'Commentaire de validation',
        ], ';');

        foreach ($rows as $row) {
            $vehicule = trim((string) ($row['vehicule_marque'] ?? '') . ' ' . (string) ($row['vehicule_type'] ?? ''));
            fputcsv($out, [
                formatDate($row['date_mission']),
                formatDate($row['date_arrivee']),
                $row['user_prenom'] . ' ' . $row['user_nom'],
                (string) ($row['departement'] ?? ''),
                $row['type_libelle'],
                $vehicule,
                (string) ($row['vehicule_type'] ?? ''),
                (string) ($row['vehicule_immatriculation'] ?? ''),
                $row['lieu_depart'],
                $row['destination'],
                formatTime($row['heure_depart']),
                formatTime($row['heure_arrivee']),
                $row['km_depart'],
                $row['km_retour'],
                $row['distance_km'],
                (string) ($row['passagers'] ?? ''),
                statutLabel($row['statut']),
                (string) ($row['remarques'] ?? ''),
                (string) ($row['commentaire_validation'] ?? ''),
            ], ';');
        }

        fclose($out);
        exit;
    }

    public function create(): void
    {
        $this->requireAuth();

        $this->render('missions/create', [
            'title' => 'Nouvelle mission',
            'mission' => null,
            'types' => TypeMission::all(true),
            'users' => User::active(),
            'vehicules' => Vehicule::all(true),
            'passagers' => [['nom' => '', 'prenom' => '']],
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        $payload = $this->validate($_POST, null);
        if ($payload['errors'] !== []) {
            $this->redirectBackWithErrors('/missions/create', $payload['errors'], $_POST);
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $id = Mission::create($payload['data']);
            Passager::sync($id, $payload['passagers'], (int) $payload['data']['user_id']);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }

        Flash::success('La mission a été enregistrée. Elle est en attente de validation.');
        $this->redirect('/missions/' . $id);
    }

    public function show(string $id): void
    {
        $this->requireAuth();
        $mission = $this->missionOrFail($id);
        $this->assertCanView($mission);

        $this->render('missions/show', [
            'title' => 'Mission du ' . formatDate($mission['date_mission']),
            'mission' => $mission,
            'passagers' => Passager::forMission((int) $mission['id']),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $mission = $this->missionOrFail($id);
        $this->assertCanEdit($mission);

        $this->render('missions/edit', [
            'title' => 'Modifier la mission',
            'mission' => $mission,
            'types' => TypeMission::all(false),
            'users' => User::active(),
            'vehicules' => Vehicule::all(false),
            'passagers' => Passager::forMission((int) $mission['id']) ?: [['nom' => '', 'prenom' => '']],
        ]);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $mission = $this->missionOrFail($id);
        $this->assertCanEdit($mission);

        $payload = $this->validate($_POST, $mission);
        if ($payload['errors'] !== []) {
            $this->redirectBackWithErrors('/missions/' . $mission['id'] . '/edit', $payload['errors'], $_POST);
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            Mission::update((int) $mission['id'], $payload['data']);
            Passager::sync((int) $mission['id'], $payload['passagers'], (int) $payload['data']['user_id']);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }

        Flash::success('La mission a été mise à jour.');
        $this->redirect('/missions/' . $mission['id']);
    }

    public function destroy(string $id): void
    {
        $this->requireRole(['admin', 'responsable']);
        $mission = $this->missionOrFail($id);
        Mission::delete((int) $mission['id']);
        Flash::success('La mission a été supprimée.');
        $this->redirect('/missions');
    }

    public function status(string $id): void
    {
        $user = $this->requireRole(['admin', 'responsable']);
        $mission = $this->missionOrFail($id);
        $statut = (string) ($_POST['statut'] ?? '');

        if (!in_array($statut, ['en_attente', 'validee', 'refusee'], true)) {
            Flash::danger('Statut inconnu.');
            $this->redirect('/missions/' . $mission['id']);
        }

        $commentaire = trim((string) ($_POST['commentaire'] ?? ''));
        if (mb_strlen($commentaire) > 1000) {
            Flash::danger('Le commentaire est limité à 1 000 caractères.');
            $this->redirect('/missions/' . $mission['id']);
        }

        if ($statut === 'refusee' && mb_strlen($commentaire) < 3) {
            Flash::danger('Un refus doit être accompagné d\'un commentaire.');
            $this->redirect('/missions/' . $mission['id']);
        }

        Mission::setStatus(
            (int) $mission['id'],
            $statut,
            (int) $user['id'],
            $commentaire === '' ? null : $commentaire
        );

        $message = match ($statut) {
            'validee' => 'La mission a été validée.',
            'refusee' => 'La mission a été refusée.',
            default => 'La mission a été remise en attente.',
        };

        Flash::success($message);
        $this->redirect('/missions/' . $mission['id']);
    }

    private function filtersFromRequest(): array
    {
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'user_id' => (string) ($_GET['user_id'] ?? ''),
            'statut' => (string) ($_GET['statut'] ?? ''),
            'date_debut' => (string) ($_GET['date_debut'] ?? ''),
            'date_fin' => (string) ($_GET['date_fin'] ?? ''),
        ];

        if (mb_strlen($filters['q']) > 100) {
            $filters['q'] = mb_substr($filters['q'], 0, 100);
        }

        if (!$this->isDate($filters['date_debut'])) {
            $filters['date_debut'] = '';
        }

        if (!$this->isDate($filters['date_fin'])) {
            $filters['date_fin'] = '';
        }

        if ($filters['date_debut'] !== '' && $filters['date_fin'] !== '' && $filters['date_debut'] > $filters['date_fin']) {
            Flash::danger('La date de début est postérieure à la date de fin. Le filtre de dates a été ignoré.');
            $filters['date_debut'] = '';
            $filters['date_fin'] = '';
        }

        if (!Auth::managesMissions()) {
            $filters['user_id'] = '';
        }

        return $filters;
    }

    private function missionOrFail(string $id): array
    {
        if (!ctype_digit($id)) {
            $this->notFound();
        }

        $mission = Mission::find((int) $id);
        if ($mission === null) {
            $this->notFound();
        }

        return $mission;
    }

    private function assertCanView(array $mission): void
    {
        if (!Auth::managesMissions() && (int) $mission['user_id'] !== Auth::id()) {
            $this->forbid();
        }
    }

    private function assertCanEdit(array $mission): void
    {
        $this->assertCanView($mission);

        if (!Auth::managesMissions() && $mission['statut'] === 'validee') {
            Flash::danger('Une mission validée ne peut plus être modifiée par l\'enseignant.');
            $this->redirect('/missions/' . $mission['id']);
        }
    }

    private function validate(array $post, ?array $current): array
    {
        $errors = [];
        $passagers = $this->passagersFromPost($post);

        if (Auth::role() === 'enseignant') {
            $userId = Auth::id();
        } else {
            $userId = (int) ($post['user_id'] ?? 0);
            $owner = User::find($userId);
            if ($owner === null || (int) $owner['actif'] !== 1) {
                $errors['user_id'] = 'Sélectionnez un enseignant actif.';
            }
        }

        $typeId = (int) ($post['type_mission_id'] ?? 0);
        $type = TypeMission::find($typeId);
        $currentTypeId = $current === null ? 0 : (int) $current['type_mission_id'];
        if ($type === null || ((int) $type['actif'] !== 1 && $typeId !== $currentTypeId)) {
            $errors['type_mission_id'] = 'Sélectionnez la nature de la mission.';
        }

        $date = trim((string) ($post['date_mission'] ?? ''));
        if (!$this->isDate($date)) {
            $errors['date_mission'] = 'Indiquez une date de départ valide.';
        }

        $dateArrivee = trim((string) ($post['date_arrivee'] ?? ''));
        if ($dateArrivee === '') {
            $dateArrivee = $date;
        }
        if (!$this->isDate($dateArrivee)) {
            $errors['date_arrivee'] = 'Indiquez une date d\'arrivée valide.';
        } elseif ($this->isDate($date) && $dateArrivee < $date) {
            $errors['date_arrivee'] = 'La date d\'arrivée ne peut pas précéder la date de départ.';
        }

        $lieu = trim((string) ($post['lieu_depart'] ?? ''));
        if ($lieu === '' || mb_strlen($lieu) > 150) {
            $errors['lieu_depart'] = 'Indiquez le lieu de départ (150 caractères maximum).';
        }

        $destination = trim((string) ($post['destination'] ?? ''));
        if ($destination === '' || mb_strlen($destination) > 150) {
            $errors['destination'] = 'Indiquez la destination (150 caracteres maximum).';
        }

        $heureDepart = $this->normalizeTime((string) ($post['heure_depart'] ?? ''));
        $heureArrivee = $this->normalizeTime((string) ($post['heure_arrivee'] ?? ''));
        if ($heureDepart === null) {
            $errors['heure_depart'] = 'Indiquez une heure de depart valide.';
        }
        if ($heureArrivee === null) {
            $errors['heure_arrivee'] = 'Indiquez une heure d\'arrivee valide.';
        }
        $sameDay = $this->isDate($date) && $this->isDate($dateArrivee) && $dateArrivee === $date;
        if ($sameDay && $heureDepart !== null && $heureArrivee !== null && $heureArrivee < $heureDepart) {
            $errors['heure_arrivee'] = 'Le jour du départ, l\'heure d\'arrivée doit être postérieure à l\'heure de départ.';
        }

        $vehiculeId = (int) ($post['vehicule_id'] ?? 0);
        $vehicule = $vehiculeId > 0 ? Vehicule::find($vehiculeId) : null;
        $currentVehiculeId = $current === null ? 0 : (int) ($current['vehicule_id'] ?? 0);
        if ($vehicule === null || ((int) $vehicule['actif'] !== 1 && $vehiculeId !== $currentVehiculeId)) {
            $errors['vehicule_id'] = 'Sélectionnez le véhicule pris pour la mission.';
        }

        $kmDepartRaw = trim((string) ($post['km_depart'] ?? ''));
        $kmRetourRaw = trim((string) ($post['km_retour'] ?? ''));
        $kmDepart = $kmDepartRaw === '' ? null : $this->kilometers($kmDepartRaw);
        $kmRetour = $kmRetourRaw === '' ? null : $this->kilometers($kmRetourRaw);
        $isBike = $vehicule !== null && isVelo((string) $vehicule['type']);

        if ($kmDepartRaw !== '' && $kmDepart === null) {
            $errors['km_depart'] = 'Le kilométrage à la prise du véhicule est invalide.';
        }
        if ($kmRetourRaw !== '' && $kmRetour === null) {
            $errors['km_retour'] = 'Le kilométrage à la restitution du véhicule est invalide.';
        }

        if ($isBike) {
            if (($kmDepart === null) !== ($kmRetour === null)) {
                $errors['km_retour'] = 'Pour un vélo, laissez les deux compteurs vides.';
            }
        } else {
            if ($kmDepart === null) {
                $errors['km_depart'] = 'Indiquez le kilométrage à la prise du véhicule.';
            }
            if ($kmRetour === null) {
                $errors['km_retour'] = 'Indiquez le kilométrage à la restitution du véhicule.';
            }
        }

        if ($kmDepart !== null && $kmRetour !== null && $kmRetour < $kmDepart) {
            $errors['km_retour'] = 'Le kilométrage à la restitution doit être supérieur ou égal à celui de la prise.';
        }

        $remarques = trim((string) ($post['remarques'] ?? ''));
        if (mb_strlen($remarques) > 2000) {
            $errors['remarques'] = 'Les remarques ne peuvent pas dépasser 2 000 caractères.';
        }

        foreach ($passagers as $passager) {
            $hasNom = $passager['nom'] !== '';
            $hasPrenom = $passager['prenom'] !== '';
            if ($hasNom !== $hasPrenom) {
                $errors['passagers'] = 'Chaque personne transportée doit avoir un nom et un prénom.';
                break;
            }
            if (mb_strlen($passager['nom']) > 100 || mb_strlen($passager['prenom']) > 100) {
                $errors['passagers'] = 'Le nom et le prénom d\'une personne sont limités à 100 caractères.';
                break;
            }
            if ($hasNom && $hasPrenom && User::findByName($passager['nom'], $passager['prenom']) === null) {
                $errors['passagers'] = 'Chaque personne transportée doit déjà figurer dans l\'annuaire.';
                break;
            }
        }

        return [
            'errors' => $errors,
            'passagers' => $passagers,
            'data' => [
                'user_id' => $userId,
                'type_mission_id' => $typeId,
                'vehicule_id' => $vehiculeId > 0 ? $vehiculeId : null,
                'date_mission' => $date,
                'date_arrivee' => $dateArrivee,
                'lieu_depart' => $lieu,
                'destination' => $destination,
                'heure_depart' => $heureDepart ?? '00:00:00',
                'heure_arrivee' => $heureArrivee ?? '00:00:00',
                'km_depart' => $kmDepart,
                'km_retour' => $kmRetour,
                'remarques' => $remarques === '' ? null : $remarques,
            ],
        ];
    }

    private function passagersFromPost(array $post): array
    {
        $rows = $post['passagers'] ?? [];
        if (!is_array($rows)) {
            return [];
        }

        $clean = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $clean[] = [
                'nom' => trim((string) ($row['nom'] ?? '')),
                'prenom' => trim((string) ($row['prenom'] ?? '')),
            ];
        }

        return $clean;
    }

    private function isDate(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date instanceof \DateTimeImmutable && $date->format('Y-m-d') === $value;
    }

    private function normalizeTime(string $value): ?string
    {
        $value = trim($value);
        if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value) === 1) {
            return $value . ':00';
        }

        if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $value) === 1) {
            return $value;
        }

        return null;
    }

    private function kilometers(mixed $value): ?int
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '' || preg_match('/^\d{1,7}$/', $raw) !== 1) {
            return null;
        }

        return (int) $raw;
    }
}
