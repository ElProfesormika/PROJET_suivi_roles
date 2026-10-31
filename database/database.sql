-- Gestion des missions du personnel enseignant
-- Universite Jean Monnet - IUT de Roanne
--
-- Import :
--   1. Creer la base (en local : mission_ujm) dans phpMyAdmin.
--   2. La selectionner.
--   3. Importer ce fichier.
--
-- Sur InfinityFree, la base est deja creee dans le panneau.
-- Ne pas executer CREATE DATABASE : l'hebergeur le refuse souvent.
--
-- En ligne de commande, apres creation de la base :
--   mysql -u root -p mission_ujm < database/database.sql
--
-- Comptes initiaux (a modifier des la premiere connexion) :
--   admin@ujm-roanne.fr        / Admin123!
--   responsable@ujm-roanne.fr  / Responsable123!

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS accompagner;
DROP TABLE IF EXISTS mission_passagers;
DROP TABLE IF EXISTS trajet;
DROP TABLE IF EXISTS missions;
DROP TABLE IF EXISTS passagers;
DROP TABLE IF EXISTS vehicule;
DROP TABLE IF EXISTS vehicules;
DROP TABLE IF EXISTS types_missions;
DROP TABLE IF EXISTS personne;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- 
-- Personnes
-- Le mot de passe et le role completent le registre du professeur.
-- Roles : admin, responsable, enseignant
-- 
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

-- 
-- Natures de mission
-- 
CREATE TABLE types_missions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    libelle VARCHAR(120) NOT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_types_missions_libelle (libelle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Vehicules du parc
-- type : Megane, 3008, Velo
-- 
CREATE TABLE vehicule (
    idVehicule INT UNSIGNED NOT NULL AUTO_INCREMENT,
    immatriculation VARCHAR(50) NOT NULL,
    marque VARCHAR(50) NOT NULL,
    type VARCHAR(50) NOT NULL,
    PRIMARY KEY (idVehicule)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Trajets
-- Statuts : en_attente, validee, refusee
-- La distance n'est pas stockee : elle vaut kilometrageRetour - kilometrageDepart.
-- Le type Velo, ou un trajet sans vehicule, peut n'avoir aucun kilometrage.
-- 
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
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_trajet_vehicule
        FOREIGN KEY (idVehicule) REFERENCES vehicule (idVehicule)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_trajet_validateur
        FOREIGN KEY (validated_by) REFERENCES personne (idPersonne)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT chk_trajet_dates CHECK (dateArrivee >= dateDepart),
    CONSTRAINT chk_trajet_km CHECK (
        (kilometrageDepart IS NULL AND kilometrageRetour IS NULL)
        OR (kilometrageDepart IS NOT NULL AND kilometrageRetour IS NOT NULL AND kilometrageRetour >= kilometrageDepart)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Personnes qui accompagnent un trajet
-- 
CREATE TABLE accompagner (
    idTrajet INT UNSIGNED NOT NULL,
    idPersonne INT UNSIGNED NOT NULL,
    PRIMARY KEY (idTrajet, idPersonne),
    KEY idx_accompagner_personne (idPersonne),
    CONSTRAINT fk_accompagner_trajet
        FOREIGN KEY (idTrajet) REFERENCES trajet (idTrajet)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_accompagner_personne
        FOREIGN KEY (idPersonne) REFERENCES personne (idPersonne)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Donnees initiales
-- Les empreintes sont des hash bcrypt (password_hash / password_verify).
-- 
INSERT INTO personne (civilite, nom, prenom, mail, password_hash, role, departement, telephone, actif) VALUES
(
    NULL,
    'IUT',
    'Admin',
    'admin@ujm-roanne.fr',
    '$2y$12$O.bpWKTTox3pHKoQVXOKd.82fz9gAMb8h7fKMWPaXtdmyRJHNyftK',
    'admin',
    'IUT de Roanne',
    NULL,
    1
),
(
    NULL,
    'IUT',
    'Responsable',
    'responsable@ujm-roanne.fr',
    '$2y$12$osoktLFLIxQLXzhOa4IeJuLysuJDTxlHwxEB7pt.3kuD34mVE4YUC',
    'responsable',
    'IUT de Roanne',
    NULL,
    1
);

INSERT INTO types_missions (libelle, actif) VALUES
('Réunion', 1),
('Formation', 1),
('Déplacement administratif', 1),
('Visite / représentation', 1),
('Mission pédagogique', 1),
('Conférence / colloque', 1),
('Autre', 1),
('Visite stagiaire', 1),
('Visite alternant', 1);

INSERT INTO vehicule (immatriculation, marque, type) VALUES
('EM-240-EH', 'Renault', 'Megane'),
('DR-479-KJ', 'Peugeot', '3008'),
('', 'Mercier', 'Velo');

-- Enseignants du registre historique.
-- Mot de passe initial commun : Enseignant123!
INSERT INTO personne (civilite, nom, prenom, mail, password_hash, role, departement, telephone, actif) VALUES
('M', 'Khennouf', 'Salah', 'salah.khennouf@univ-st-etienne.fr', '$2y$10$ZuLzRBaRymXZiz.nDatZZuz7p7lJasW7148EsZCRVT..9dArbe8N2', 'enseignant', 'IUT de Roanne', NULL, 1),
('M', 'Mammad', 'Leize', 'leize.mammad@univ-st-etienne.fr', '$2y$10$ZuLzRBaRymXZiz.nDatZZuz7p7lJasW7148EsZCRVT..9dArbe8N2', 'enseignant', 'IUT de Roanne', NULL, 1),
('M', 'Moussiere', 'Laurent', 'laurent.moussiere@univ-st-etienne.fr', '$2y$10$ZuLzRBaRymXZiz.nDatZZuz7p7lJasW7148EsZCRVT..9dArbe8N2', 'enseignant', 'IUT de Roanne', NULL, 1),
('M', 'Masmoudi', 'Malek', 'malek.masmoudi@univ-st-etienne.fr', '$2y$10$ZuLzRBaRymXZiz.nDatZZuz7p7lJasW7148EsZCRVT..9dArbe8N2', 'enseignant', 'IUT de Roanne', NULL, 1),
('M', 'Lamraoui', 'Mourad', 'mourad.lamraoui@univ-st-etienne.fr', '$2y$10$ZuLzRBaRymXZiz.nDatZZuz7p7lJasW7148EsZCRVT..9dArbe8N2', 'enseignant', 'IUT de Roanne', NULL, 1),
('M', 'Guillet', 'Francois', 'francois.guillet@univ-st-etienne.fr', '$2y$10$ZuLzRBaRymXZiz.nDatZZuz7p7lJasW7148EsZCRVT..9dArbe8N2', 'enseignant', 'IUT de Roanne', NULL, 1),
('Mlle', 'Rim', 'Faraj', 'faraj.rim@univ-st-etienne.fr', '$2y$10$ZuLzRBaRymXZiz.nDatZZuz7p7lJasW7148EsZCRVT..9dArbe8N2', 'enseignant', 'IUT de Roanne', NULL, 1);
