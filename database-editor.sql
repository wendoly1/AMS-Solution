-- Ejecutar en la misma base de datos del sitio. No elimina contenido existente.
CREATE TABLE IF NOT EXISTS site_revisions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section VARCHAR(30) NOT NULL,
    username VARCHAR(80) NOT NULL,
    action_label VARCHAR(150) NOT NULL,
    before_data LONGTEXT NOT NULL,
    after_data LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX revisions_created (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contacto_estado (
    contacto_id INT NOT NULL PRIMARY KEY,
    estado VARCHAR(20) NOT NULL DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contactos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    telefono VARCHAR(30),
    area VARCHAR(100),
    consulta TEXT NOT NULL,
    creado_en DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
