<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    private const COLUMNS = 'idPersonne AS id, civilite, nom, prenom, mail AS email, password_hash, role, departement, telephone, actif, created_at, updated_at';

    public static function find(int $id): ?array
    {
        $stmt = self::pdo()->prepare('SELECT ' . self::COLUMNS . ' FROM personne WHERE idPersonne = ? LIMIT 1');
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        return $user === false ? null : $user;
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = self::pdo()->prepare('SELECT ' . self::COLUMNS . ' FROM personne WHERE mail = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        return $user === false ? null : $user;
    }

    public static function findByName(string $nom, string $prenom): ?array
    {
        $stmt = self::pdo()->prepare(
            'SELECT ' . self::COLUMNS . ' FROM personne WHERE nom = ? AND prenom = ? LIMIT 1'
        );
        $stmt->execute([$nom, $prenom]);
        $user = $stmt->fetch();

        return $user === false ? null : $user;
    }

    public static function all(): array
    {
        $stmt = self::pdo()->query(
            'SELECT idPersonne AS id, civilite, nom, prenom, mail AS email, role, departement, telephone, actif, created_at
             FROM personne
             ORDER BY nom, prenom'
        );

        return $stmt->fetchAll();
    }

    public static function active(): array
    {
        $stmt = self::pdo()->query(
            'SELECT idPersonne AS id, nom, prenom, mail AS email, role, departement
             FROM personne
             WHERE actif = 1
             ORDER BY nom, prenom'
        );

        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $stmt = self::pdo()->prepare(
            'INSERT INTO personne (nom, prenom, mail, password_hash, role, departement, telephone, actif)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['nom'],
            $data['prenom'],
            $data['email'],
            $data['password_hash'],
            $data['role'],
            $data['departement'],
            $data['telephone'],
            $data['actif'],
        ]);

        return (int) self::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = self::pdo()->prepare(
            'UPDATE personne
             SET nom = ?, prenom = ?, mail = ?, role = ?, departement = ?, telephone = ?, actif = ?
             WHERE idPersonne = ?'
        );
        $stmt->execute([
            $data['nom'],
            $data['prenom'],
            $data['email'],
            $data['role'],
            $data['departement'],
            $data['telephone'],
            $data['actif'],
            $id,
        ]);
    }

    public static function updatePassword(int $id, string $passwordHash): void
    {
        $stmt = self::pdo()->prepare('UPDATE personne SET password_hash = ? WHERE idPersonne = ?');
        $stmt->execute([$passwordHash, $id]);
    }

    public static function setActif(int $id, int $actif): void
    {
        $stmt = self::pdo()->prepare('UPDATE personne SET actif = ? WHERE idPersonne = ?');
        $stmt->execute([$actif, $id]);
    }

    public static function countActiveAdmins(?int $exceptId = null): int
    {
        if ($exceptId === null) {
            $stmt = self::pdo()->query(
                "SELECT COUNT(*) FROM personne WHERE role = 'admin' AND actif = 1"
            );

            return (int) $stmt->fetchColumn();
        }

        $stmt = self::pdo()->prepare(
            "SELECT COUNT(*) FROM personne WHERE role = 'admin' AND actif = 1 AND idPersonne <> ?"
        );
        $stmt->execute([$exceptId]);

        return (int) $stmt->fetchColumn();
    }
}
