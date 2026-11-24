CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    firebase_uid VARCHAR(128) NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    username VARCHAR(50) DEFAULT NULL,
    email VARCHAR(254) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY firebase_uid (firebase_uid),
    UNIQUE KEY email (email),
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS projects (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(160) NOT NULL,
    project_type ENUM(
        'architecture',
        'interior_design',
        'other'
    ) NOT NULL DEFAULT 'other',
    description TEXT,
    location VARCHAR(255) DEFAULT NULL,
    area_sqft DECIMAL(10,2) DEFAULT NULL,
    budget DECIMAL(12,2) DEFAULT NULL,
    status ENUM(
        'draft',
        'submitted',
        'in_progress',
        'completed',
        'archived'
    ) NOT NULL DEFAULT 'submitted',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_projects_user (user_id),
    CONSTRAINT fk_projects_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS design_preferences (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED DEFAULT NULL,
    space_type VARCHAR(80) DEFAULT NULL,
    preferred_style VARCHAR(100) DEFAULT NULL,
    color_palette VARCHAR(120) DEFAULT NULL,
    budget_min DECIMAL(12,2) DEFAULT NULL,
    budget_max DECIMAL(12,2) DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY fk_preferences_user (user_id),
    KEY fk_preferences_project (project_id),
    CONSTRAINT fk_preferences_project
        FOREIGN KEY (project_id) REFERENCES projects (id)
        ON DELETE SET NULL,
    CONSTRAINT fk_preferences_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enquiries (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED DEFAULT NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(254) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    service_type ENUM(
        'architecture',
        'interior_design',
        'consultation',
        'other'
    ) NOT NULL DEFAULT 'other',
    subject VARCHAR(180) DEFAULT NULL,
    message TEXT NOT NULL,
    status ENUM(
        'new',
        'in_review',
        'responded',
        'closed'
    ) NOT NULL DEFAULT 'new',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_enquiries_status (status),
    KEY fk_enquiries_user (user_id),
    CONSTRAINT fk_enquiries_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
