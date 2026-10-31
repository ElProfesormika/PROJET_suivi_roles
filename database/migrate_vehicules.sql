-- Ajoute le parc de vehicules et la prise / restitution sur une base deja installee.

CREATE TABLE IF NOT EXISTS vehicules (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    immatriculation VARCHAR(50) NOT NULL DEFAULT '',
    marque VARCHAR(50) NOT NULL,
    type VARCHAR(50) NOT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_vehicules_immatriculation (immatriculation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE missions
    ADD COLUMN vehicule_id INT UNSIGNED NULL AFTER type_mission_id,
    ADD COLUMN date_arrivee DATE NULL AFTER date_mission;

UPDATE missions SET date_arrivee = date_mission WHERE date_arrivee IS NULL;

ALTER TABLE missions
    MODIFY date_arrivee DATE NOT NULL,
    MODIFY km_depart INT UNSIGNED NULL,
    MODIFY km_retour INT UNSIGNED NULL;

ALTER TABLE missions DROP CHECK chk_missions_km;

ALTER TABLE missions
    ADD CONSTRAINT chk_missions_dates CHECK (date_arrivee >= date_mission),
    ADD CONSTRAINT chk_missions_km CHECK (
        (km_depart IS NULL AND km_retour IS NULL)
        OR (km_depart IS NOT NULL AND km_retour IS NOT NULL AND km_retour >= km_depart)
    ),
    ADD KEY idx_missions_vehicule (vehicule_id),
    ADD CONSTRAINT fk_missions_vehicule
        FOREIGN KEY (vehicule_id) REFERENCES vehicules (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL;
