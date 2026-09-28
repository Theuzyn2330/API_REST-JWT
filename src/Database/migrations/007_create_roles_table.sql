CREATE TABLE IF NOT EXISTS roles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(32) NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY roles_code_unique (code)
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO roles (id, code, name) VALUES
    (1, 'admin', 'Administrator'),
    (2, 'manager', 'Manager'),
    (3, 'client', 'Client')
ON DUPLICATE KEY UPDATE name = VALUES(name);

ALTER TABLE users
    ADD COLUMN role_id BIGINT UNSIGNED NOT NULL DEFAULT 3 AFTER password,
    ADD KEY users_role_id_index (role_id),
    ADD CONSTRAINT users_role_id_foreign FOREIGN KEY (role_id)
        REFERENCES roles (id) ON UPDATE CASCADE ON DELETE RESTRICT;