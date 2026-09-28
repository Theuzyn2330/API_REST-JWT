CREATE TABLE IF NOT EXISTS prices (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    source_id BIGINT UNSIGNED NOT NULL,
    price DECIMAL(14,4) UNSIGNED NOT NULL,
    unit VARCHAR(50) NOT NULL,
    location VARCHAR(191) NULL,
    collected_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY prices_product_collected_index (product_id, collected_at),
    KEY prices_source_collected_index (source_id, collected_at),
    CONSTRAINT prices_product_id_foreign FOREIGN KEY (product_id)
        REFERENCES products (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT prices_source_id_foreign FOREIGN KEY (source_id)
        REFERENCES sources (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci;