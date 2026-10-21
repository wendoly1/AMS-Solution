-- Tablas adicionales para recuperación. admin/auth.php agrega email y auth_version
-- a admin_users si faltan, conservando usuarios y contraseñas existentes.
CREATE TABLE IF NOT EXISTS admin_password_resets (
    token_hash CHAR(64) PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    auth_version INT UNSIGNED NOT NULL,
    expires_at BIGINT NOT NULL,
    used_at BIGINT NULL,
    created_at BIGINT NOT NULL,
    INDEX reset_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_auth_limits (
    bucket_hash CHAR(64) PRIMARY KEY,
    window_started BIGINT NOT NULL,
    attempts INT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
