-- Aligne les tables sur le registre : personne, vehicule, trajet, accompagner.
-- Conserve les mots de passe, les roles et la validation.

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE personne (
    idPersonne INT UNSIGNED NOT NULL AUTO_INCREMENT,
    civilite VARCHAR(4) NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    mail VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'responsable', 'enseignant') NOT NULL DEFAULT 'enseignant',
    departement VARCHAR(120) NULL,
    telephone VARCHAR(30) NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (idPersonne),
    UNIQUE KEY uq_personne_mail (mail),
    KEY idx_personne_role (role),
    KEY idx_personne_actif (actif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO personne (
    idPersonne, civilite, nom, prenom, mail, password_hash, role,
    departement, telephone, actif, created_at, updated_at
)
SELECT
    id,
    CASE email
        WHEN 'salah.khennouf@univ-st-etienne.fr' THEN 'M'
        WHEN 'leize.mammad@univ-st-etienne.fr' THEN 'M'
        WHEN 'laurent.moussiere@univ-st-etienne.fr' THEN 'M'
        WHEN 'malek.masmoudi@univ-st-etienne.fr' THEN 'M'
        WHEN 'mourad.lamraoui@univ-st-etienne.fr' THEN 'M'
        WHEN 'francois.guillet@univ-st-etienne.fr' THEN 'M'
        WHEN 'faraj.rim@univ-st-etienne.fr' THEN 'Mlle'
        ELSE NULL
    END,
    nom, prenom, email, password_hash, role, departement, telephone, actif, created_at, updated_at
FROM users;

CREATE TABLE vehicule (
    idVehicule INT UNSIGNED NOT NULL AUTO_INCREMENT,
    immatriculation VARCHAR(50) NOT NULL,
    marque VARCHAR(50) NOT NULL,
    type VARCHAR(50) NOT NULL,
    PRIMARY KEY (idVehicule)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO vehicule (idVehicule, immatriculation, marque, type)
SELECT id, immatriculation, marque, type FROM vehicules;

CREATE TABLE trajet (
    idTrajet INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nature VARCHAR(120) NOT NULL,
    dateDepart DATE NOT NULL,
    heureDepart TIME NOT NULL,
    dateArrivee DATE NOT NULL,
    heureArrivee TIME NOT NULL,
    lieuDepart VARCHAR(150) NOT NULL,
    destination VARCHAR(150) NOT NULL,
    kilometrageDepart INT NULL,
    kilometrageRetour INT NULL,
    idPersonne INT UNSIGNED NOT NULL,
    idVehicule INT UNSIGNED NULL,
    remarques TEXT NULL,
    statut ENUM('en_attente', 'validee', 'refusee') NOT NULL DEFAULT 'en_attente',
    validated_by INT UNSIGNED NULL,
    validated_at DATETIME NULL,
    commentaire_validation TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (idTrajet),
    KEY idx_trajet_personne_date (idPersonne, dateDepart),
    KEY idx_trajet_statut_date (statut, dateDepart),
    KEY idx_trajet_destination (destination),
    KEY idx_trajet_vehicule (idVehicule),
    KEY idx_trajet_validated_by (validated_by),
    CONSTRAINT fk_trajet_personne
        FOREIGN KEY (idPersonne) REFERENCES personne (idPersonne)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_trajet_vehicule
        FOREIGN KEY (idVehicule) REFERENCES vehicule (idVehicule)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_trajet_validateur
        FOREIGN KEY (validated_by) REFERENCES personne (idPersonne)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_trajet_dates CHECK (dateArrivee >= dateDepart),
    CONSTRAINT chk_trajet_km CHECK (
        (kilometrageDepart IS NULL AND kilometrageRetour IS NULL)
        OR (kilometrageDepart IS NOT NULL AND kilometrageRetour IS NOT NULL AND kilometrageRetour >= kilometrageDepart)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO trajet (
    idTrajet, nature, dateDepart, heureDepart, dateArrivee, heureArrivee,
    lieuDepart, destination, kilometrageDepart, kilometrageRetour,
    idPersonne, idVehicule, remarques, statut, validated_by, validated_at,
    commentaire_validation, created_at, updated_at
)
SELECT
    m.id, t.libelle, m.date_mission, m.heure_depart, m.date_arrivee, m.heure_arrivee,
    m.lieu_depart, m.destination, m.km_depart, m.km_retour,
    m.user_id, m.vehicule_id, m.remarques, m.statut, m.validated_by, m.validated_at,
    m.commentaire_validation, m.created_at, m.updated_at
FROM missions m
INNER JOIN types_missions t ON t.id = m.type_mission_id;

CREATE TABLE accompagner (
    idTrajet INT UNSIGNED NOT NULL,
    idPersonne INT UNSIGNED NOT NULL,
    PRIMARY KEY (idTrajet, idPersonne),
    KEY idx_accompagner_personne (idPersonne),
    CONSTRAINT fk_accompagner_trajet
        FOREIGN KEY (idTrajet) REFERENCES trajet (idTrajet)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_accompagner_personne
        FOREIGN KEY (idPersonne) REFERENCES personne (idPersonne)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO accompagner (idTrajet, idPersonne)
SELECT mp.mission_id, u.id
FROM mission_passagers mp
INNER JOIN passagers p ON p.id = mp.passager_id
INNER JOIN users u ON u.nom = p.nom AND u.prenom = p.prenom;

DROP TABLE mission_passagers;
DROP TABLE passagers;
DROP TABLE missions;
DROP TABLE vehicules;
DROP TABLE users;

SET FOREIGN_KEY_CHECKS = 1;
