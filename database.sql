-- Ejecuta esto en phpMyAdmin (pestaña SQL) para crear la base de datos y la tabla

CREATE DATABASE IF NOT EXISTS ams_legal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ams_legal;

CREATE TABLE IF NOT EXISTS contactos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    telefono VARCHAR(30),
    area VARCHAR(100),
    consulta TEXT NOT NULL,
    creado_en DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(190) NULL,
    auth_version INT UNSIGNED NOT NULL DEFAULT 1,
    UNIQUE KEY admin_email_unique (email),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(120) PRIMARY KEY,
    setting_value LONGTEXT NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Si ya tenías la tabla creada de antes, agrega la columna nueva con:
-- ALTER TABLE contactos ADD COLUMN area VARCHAR(100) AFTER telefono;
