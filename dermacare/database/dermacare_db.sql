

CREATE DATABASE IF NOT EXISTS dermacare_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dermacare_db;


CREATE TABLE IF NOT EXISTS users (
    user_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120)  NOT NULL,
    email       VARCHAR(191)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role        ENUM('patient','dermatologist','admin') NOT NULL DEFAULT 'patient',
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS cases (
    case_id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id       INT UNSIGNED NOT NULL,
    dermatologist_id INT UNSIGNED DEFAULT NULL,
    title            VARCHAR(255) NOT NULL,
    description      TEXT        NOT NULL,
    status           ENUM('submitted','in_review','completed') NOT NULL DEFAULT 'submitted',
    feedback         TEXT        DEFAULT NULL,
    created_at       DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id)       REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (dermatologist_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS images (
    image_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    case_id        INT UNSIGNED NOT NULL,
    file_path      VARCHAR(512) NOT NULL,
    thumbnail_path VARCHAR(512) DEFAULT NULL,
    uploaded_at    DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(case_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS audit_log (
    log_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id   INT UNSIGNED DEFAULT NULL,
    action    VARCHAR(100) NOT NULL,
    details   TEXT         DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    timestamp DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


INSERT INTO users (name, email, password_hash, role) VALUES
('Admin', 'admin@dermacare.local',
 '$2y$12$eImiTXuWVxfM37uY4JANjQ==.EXAMPLE_HASH_CHANGE_ME', 'admin');
