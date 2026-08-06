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

-- Si ya tenías la tabla creada de antes, agrega la columna nueva con:
-- ALTER TABLE contactos ADD COLUMN area VARCHAR(100) AFTER telefono;
