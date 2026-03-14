-- ============================================================
--  DrinkLog – Database Schema
--  Run once: mysql -u your_user -p your_db < install.sql
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ------------------------------------------------------------
-- Users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `username`         VARCHAR(50)     NOT NULL,
    `email`            VARCHAR(255)    NOT NULL,
    `password_hash`    VARCHAR(255)    NOT NULL,
    `theme`            ENUM('light','dark') NOT NULL DEFAULT 'light',
    `reset_token`      VARCHAR(64)     NULL DEFAULT NULL,
    `reset_expires`    DATETIME        NULL DEFAULT NULL,
    `remember_token`   VARCHAR(64)     NULL DEFAULT NULL,
    `remember_expires` DATETIME        NULL DEFAULT NULL,
    `created_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_email`            (`email`),
    UNIQUE KEY `uq_username`         (`username`),
    INDEX      `idx_reset_token`     (`reset_token`),
    INDEX      `idx_remember_token`  (`remember_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Drink log
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `drink_log` (
    `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED    NOT NULL,
    `log_date`   DATE            NOT NULL,
    `name`       VARCHAR(100)    NOT NULL,
    `volume_ml`  DECIMAL(7,1)    NOT NULL DEFAULT 0,
    `degree`     DECIMAL(5,2)    NOT NULL DEFAULT 0,
    `units`      DECIMAL(6,3)    NOT NULL DEFAULT 0,
    `is_dry_day` TINYINT(1)      NOT NULL DEFAULT 0,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_date` (`user_id`, `log_date`),
    CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Drink presets (global + per-user)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `drink_presets` (
    `id`        INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `user_id`   INT UNSIGNED  NULL DEFAULT NULL,   -- NULL = global preset
    `name`      VARCHAR(100)  NOT NULL,
    `volume_ml` DECIMAL(7,1)  NOT NULL,
    `degree`    DECIMAL(5,2)  NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_preset_user` (`user_id`),
    CONSTRAINT `fk_preset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Default global presets
-- ------------------------------------------------------------
INSERT INTO `drink_presets` (`user_id`, `name`, `volume_ml`, `degree`) VALUES
(NULL, 'Bière pression 25cl',    250,  5.0),
(NULL, 'Bière pression 33cl',    330,  5.0),
(NULL, 'Bière bouteille 33cl',   330,  5.0),
(NULL, 'Bière forte 33cl',       330,  8.5),
(NULL, 'Verre de vin blanc',     150, 12.5),
(NULL, 'Verre de vin rouge',     150, 13.5),
(NULL, 'Coupe de champagne',     125, 12.0),
(NULL, 'Shot whisky 4cl',         40, 40.0),
(NULL, 'Shot vodka 4cl',          40, 40.0),
(NULL, 'Gin tonic',              200,  7.5),
(NULL, 'Cocktail classique',     200, 10.0);
