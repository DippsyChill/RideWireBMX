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
CREATE TABLE IF NOT EXISTS rw_content_items (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, section VARCHAR(16) NOT NULL, slug VARCHAR(180) NOT NULL,
 title VARCHAR(220) NOT NULL, category VARCHAR(160) NOT NULL DEFAULT '', summary TEXT NOT NULL,
 body_html MEDIUMTEXT NOT NULL, image VARCHAR(1000) NOT NULL DEFAULT '', image_alt VARCHAR(500) NOT NULL DEFAULT '',
 image_caption VARCHAR(1000) NOT NULL DEFAULT '', country VARCHAR(120) NOT NULL DEFAULT '', discipline VARCHAR(120) NOT NULL DEFAULT '',
 highlight VARCHAR(300) NOT NULL DEFAULT '', source_url VARCHAR(1000) NOT NULL DEFAULT '', published_at VARCHAR(10) NOT NULL DEFAULT '',
 status VARCHAR(16) NOT NULL DEFAULT 'draft', sort_order INT NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id), UNIQUE KEY uq_rw_content_slug(slug),
 KEY ix_rw_content_section_status(section,status,sort_order), KEY ix_rw_content_date(published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS rw_page_views (
 view_day DATE NOT NULL, page_path VARCHAR(240) NOT NULL, view_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
 PRIMARY KEY(view_day,page_path), KEY ix_rw_views_day(view_day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
