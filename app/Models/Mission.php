<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Mission extends Model
{
    private const SELECT_BASE = 'SELECT
            m.idTrajet AS id,
            m.idPersonne AS user_id,
            t.id AS type_mission_id,
            m.idVehicule AS vehicule_id,
            m.dateDepart AS date_mission,
            m.dateArrivee AS date_arrivee,
            m.lieuDepart AS lieu_depart,
            m.destination,
            m.heureDepart AS heure_depart,
            m.heureArrivee AS heure_arrivee,
            m.kilometrageDepart AS km_depart,
            m.kilometrageRetour AS km_retour,
            m.nature,
            m.remarques,
            m.statut,
            m.validated_by,
            m.validated_at,
            m.commentaire_validation,
            m.created_at,
            m.updated_at,
            u.nom AS user_nom,
            u.prenom AS user_prenom,
            u.departement,
            m.nature AS type_libelle,
            (m.kilometrageRetour - m.kilometrageDepart) AS distance_km,
            v.nom AS validateur_nom,
            v.prenom AS validateur_prenom,
            veh.marque AS vehicule_marque,
            veh.type AS vehicule_type,
            veh.immatriculation AS vehicule_immatriculation
        FROM trajet m
        INNER JOIN personne u ON u.idPersonne = m.idPersonne
        LEFT JOIN types_missions t ON t.libelle = m.nature
        LEFT JOIN personne v ON v.idPersonne = m.validated_by
        LEFT JOIN vehicule veh ON veh.idVehicule = m.idVehicule';

    public static function search(array $filters, ?int $onlyUserId = null, ?int $limit = null, int $offset = 0): array
    {
        [$where, $params] = self::where($filters, $onlyUserId);
        $sql = self::SELECT_BASE . $where . ' ORDER BY m.dateDepart DESC, m.heureDepart DESC, m.idTrajet DESC';

        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);
        }

        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function count(array $filters, ?int $onlyUserId = null): int
    {
        [$where, $params] = self::where($filters, $onlyUserId);
        $sql = 'SELECT COUNT(*)
                FROM trajet m
                INNER JOIN personne u ON u.idPersonne = m.idPersonne' . $where;
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public static function exportRows(array $filters, ?int $onlyUserId = null): array
    {
        [$where, $params] = self::where($filters, $onlyUserId);
        $sql = 'SELECT m.dateDepart AS date_mission, m.dateArrivee AS date_arrivee,
                       u.prenom AS user_prenom, u.nom AS user_nom, u.departement,
                       m.nature AS type_libelle, m.lieuDepart AS lieu_depart, m.destination,
                       m.heureDepart AS heure_depart, m.heureArrivee AS heure_arrivee,
                       m.kilometrageDepart AS km_depart, m.kilometrageRetour AS km_retour,
                       (m.kilometrageRetour - m.kilometrageDepart) AS distance_km, m.statut,
                       veh.marque AS vehicule_marque, veh.type AS vehicule_type,
                       veh.immatriculation AS vehicule_immatriculation,
                       m.remarques, m.commentaire_validation,
                       (SELECT GROUP_CONCAT(CONCAT(p.prenom, \' \', p.nom) ORDER BY p.nom SEPARATOR \', \')
                        FROM personne p
                        INNER JOIN accompagner a ON a.idPersonne = p.idPersonne
                        WHERE a.idTrajet = m.idTrajet) AS passagers
                FROM trajet m
                INNER JOIN personne u ON u.idPersonne = m.idPersonne
                LEFT JOIN vehicule veh ON veh.idVehicule = m.idVehicule'
            . $where
            . ' ORDER BY m.dateDepart DESC, m.heureDepart DESC, m.idTrajet DESC';

        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::pdo()->prepare(self::SELECT_BASE . ' WHERE m.idTrajet = ? LIMIT 1');
        $stmt->execute([$id]);
        $mission = $stmt->fetch();

        return $mission === false ? null : $mission;
    }

    public static function create(array $data): int
    {
        $stmt = self::pdo()->prepare(
            'INSERT INTO trajet
                (idPersonne, nature, idVehicule, dateDepart, dateArrivee,
                 lieuDepart, destination, heureDepart, heureArrivee,
                 kilometrageDepart, kilometrageRetour, remarques, statut)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'en_attente\')'
        );
        $stmt->execute([
            $data['user_id'],
            self::natureOf((int) $data['type_mission_id']),
            $data['vehicule_id'],
            $data['date_mission'],
            $data['date_arrivee'],
            $data['lieu_depart'],
            $data['destination'],
            $data['heure_depart'],
            $data['heure_arrivee'],
            $data['km_depart'],
            $data['km_retour'],
            $data['remarques'],
        ]);

        return (int) self::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = self::pdo()->prepare(
            'UPDATE trajet
             SET idPersonne = ?, nature = ?, idVehicule = ?, dateDepart = ?,
                 dateArrivee = ?, lieuDepart = ?, destination = ?, heureDepart = ?,
                 heureArrivee = ?, kilometrageDepart = ?, kilometrageRetour = ?, remarques = ?
             WHERE idTrajet = ?'
        );
        $stmt->execute([
            $data['user_id'],
            self::natureOf((int) $data['type_mission_id']),
            $data['vehicule_id'],
            $data['date_mission'],
            $data['date_arrivee'],
            $data['lieu_depart'],
            $data['destination'],
            $data['heure_depart'],
            $data['heure_arrivee'],
            $data['km_depart'],
            $data['km_retour'],
            $data['remarques'],
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = self::pdo()->prepare('DELETE FROM trajet WHERE idTrajet = ?');
        $stmt->execute([$id]);
    }

    public static function setStatus(int $id, string $statut, int $validatorId, ?string $commentaire): void
    {
        if ($statut === 'en_attente') {
            $stmt = self::pdo()->prepare(
                'UPDATE trajet
                 SET statut = ?, validated_by = NULL, validated_at = NULL, commentaire_validation = NULL
                 WHERE idTrajet = ?'
            );
            $stmt->execute([$statut, $id]);
            return;
        }

        $stmt = self::pdo()->prepare(
            'UPDATE trajet
             SET statut = ?, validated_by = ?, validated_at = NOW(), commentaire_validation = ?
             WHERE idTrajet = ?'
        );
        $stmt->execute([$statut, $validatorId, $commentaire, $id]);
    }

    public static function summary(?int $onlyUserId = null): array
    {
        $sql = 'SELECT
                COUNT(*) AS total,
                COALESCE(SUM(kilometrageRetour - kilometrageDepart), 0) AS km_total,
                COALESCE(AVG(kilometrageRetour - kilometrageDepart), 0) AS km_moyen,
                COALESCE(SUM(statut = \'en_attente\'), 0) AS en_attente,
                COALESCE(SUM(statut = \'validee\'), 0) AS validees,
                COALESCE(SUM(statut = \'refusee\'), 0) AS refusees
            FROM trajet';
        $params = [];

        if ($onlyUserId !== null) {
            $sql .= ' WHERE idPersonne = ?';
            $params[] = $onlyUserId;
        }

        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row === false ? [
            'total' => 0,
            'km_total' => 0,
            'km_moyen' => 0,
            'en_attente' => 0,
            'validees' => 0,
            'refusees' => 0,
        ] : $row;
    }

    public static function recent(?int $onlyUserId = null, int $limit = 8): array
    {
        $sql = self::SELECT_BASE;
        $params = [];

        if ($onlyUserId !== null) {
            $sql .= ' WHERE m.idPersonne = ?';
            $params[] = $onlyUserId;
        }

        $sql .= ' ORDER BY m.created_at DESC, m.idTrajet DESC LIMIT ' . (int) $limit;

        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function topDestinations(?int $onlyUserId = null, int $limit = 5): array
    {
        $sql = 'SELECT destination,
                       COUNT(*) AS nb,
                       COALESCE(SUM(kilometrageRetour - kilometrageDepart), 0) AS km
                FROM trajet';
        $params = [];

        if ($onlyUserId !== null) {
            $sql .= ' WHERE idPersonne = ?';
            $params[] = $onlyUserId;
        }

        $sql .= ' GROUP BY destination
                  ORDER BY nb DESC, km DESC, destination ASC
                  LIMIT ' . (int) $limit;

        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function monthly(?int $onlyUserId = null, int $months = 6): array
    {
        $months = max(1, min(12, $months));
        $start = (new \DateTimeImmutable('first day of this month'))->modify('-' . ($months - 1) . ' months');
        $sql = 'SELECT DATE_FORMAT(dateDepart, \'%Y-%m\') AS mois,
                       COUNT(*) AS nb,
                       COALESCE(SUM(kilometrageRetour - kilometrageDepart), 0) AS km
                FROM trajet
                WHERE dateDepart >= ?';
        $params = [$start->format('Y-m-d')];

        if ($onlyUserId !== null) {
            $sql .= ' AND idPersonne = ?';
            $params[] = $onlyUserId;
        }

        $sql .= ' GROUP BY mois';
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        $found = [];
        foreach ($stmt->fetchAll() as $row) {
            $found[$row['mois']] = $row;
        }

        $labels = [1 => 'janv.', 2 => 'févr.', 3 => 'mars', 4 => 'avr.', 5 => 'mai', 6 => 'juin', 7 => 'juil.', 8 => 'août', 9 => 'sept.', 10 => 'oct.', 11 => 'nov.', 12 => 'déc.'];
        $series = [];
        $cursor = $start;
        for ($i = 0; $i < $months; $i++) {
            $key = $cursor->format('Y-m');
            $series[] = [
                'mois' => $key,
                'label' => $labels[(int) $cursor->format('n')] . ' ' . $cursor->format('Y'),
                'nb' => (int) ($found[$key]['nb'] ?? 0),
                'km' => (int) ($found[$key]['km'] ?? 0),
            ];
            $cursor = $cursor->modify('+1 month');
        }

        return $series;
    }

    public static function byTeacher(?int $limit = 8): array
    {
        $sql = 'SELECT CONCAT(u.prenom, \' \', u.nom) AS enseignant,
                       COALESCE(NULLIF(u.departement, \'\'), \'Non renseigné\') AS departement,
                       COUNT(*) AS nb,
                       COALESCE(SUM(m.kilometrageRetour - m.kilometrageDepart), 0) AS km
                FROM trajet m
                INNER JOIN personne u ON u.idPersonne = m.idPersonne
                GROUP BY u.idPersonne, u.prenom, u.nom, u.departement
                ORDER BY km DESC, nb DESC, enseignant ASC
                LIMIT ' . max(1, (int) $limit);

        return self::pdo()->query($sql)->fetchAll();
    }

    public static function byDepartment(): array
    {
        $sql = 'SELECT COALESCE(NULLIF(u.departement, \'\'), \'Non renseigné\') AS departement,
                       COUNT(*) AS nb,
                       COALESCE(SUM(m.kilometrageRetour - m.kilometrageDepart), 0) AS km
                FROM trajet m
                INNER JOIN personne u ON u.idPersonne = m.idPersonne
                GROUP BY departement
                ORDER BY km DESC, nb DESC, departement ASC';

        return self::pdo()->query($sql)->fetchAll();
    }

    private static function where(array $filters, ?int $onlyUserId): array
    {
        $sql = ' WHERE 1 = 1';
        $params = [];

        if ($onlyUserId !== null) {
            $sql .= ' AND m.idPersonne = ?';
            $params[] = $onlyUserId;
        }

        if (!empty($filters['user_id'])) {
            $sql .= ' AND m.idPersonne = ?';
            $params[] = (int) $filters['user_id'];
        }

        if (!empty($filters['statut']) && in_array($filters['statut'], ['en_attente', 'validee', 'refusee'], true)) {
            $sql .= ' AND m.statut = ?';
            $params[] = $filters['statut'];
        }

        if (!empty($filters['date_debut'])) {
            $sql .= ' AND m.dateDepart >= ?';
            $params[] = $filters['date_debut'];
        }

        if (!empty($filters['date_fin'])) {
            $sql .= ' AND m.dateDepart <= ?';
            $params[] = $filters['date_fin'];
        }

        if (!empty($filters['q'])) {
            $sql .= ' AND (
                m.destination LIKE ? ESCAPE \'\\\\\'
                OR m.lieuDepart LIKE ? ESCAPE \'\\\\\'
                OR u.nom LIKE ? ESCAPE \'\\\\\'
                OR u.prenom LIKE ? ESCAPE \'\\\\\'
                OR CONCAT(u.prenom, \' \', u.nom) LIKE ? ESCAPE \'\\\\\'
            )';
            $like = self::like((string) $filters['q']);
            array_push($params, $like, $like, $like, $like, $like);
        }

        return [$sql, $params];
    }

    private static function natureOf(int $typeId): string
    {
        $stmt = self::pdo()->prepare('SELECT libelle FROM types_missions WHERE id = ? LIMIT 1');
        $stmt->execute([$typeId]);
        $label = $stmt->fetchColumn();

        return $label === false ? '' : (string) $label;
    }

    private static function like(string $value): string
    {
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);

        return '%' . $escaped . '%';
    }
}
