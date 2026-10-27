CREATE TABLE IF NOT EXISTS rw_staff_users (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, email VARCHAR(190) NOT NULL, display_name VARCHAR(120) NOT NULL,
 password_hash VARCHAR(255) NOT NULL, role VARCHAR(16) NOT NULL, permissions TEXT NOT NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1, must_change_password TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL, last_login_at DATETIME NULL, PRIMARY KEY(id), UNIQUE KEY uq_rw_staff_email(email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rw_staff_audit (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, actor_id BIGINT UNSIGNED NULL, action VARCHAR(80) NOT NULL,
 target_id BIGINT UNSIGNED NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id), KEY ix_rw_staff_audit_time(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rw_staff_login_limits (
 fingerprint CHAR(64) NOT NULL, window_started INT UNSIGNED NOT NULL, failures TINYINT UNSIGNED NOT NULL DEFAULT 0,
 PRIMARY KEY(fingerprint)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
