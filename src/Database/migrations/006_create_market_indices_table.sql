CREATE TABLE IF NOT EXISTS market_indices (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    unit VARCHAR(50) NOT NULL,
    location VARCHAR(191) NULL,
    average_price DECIMAL(14,4) UNSIGNED NOT NULL,
    minimum_price DECIMAL(14,4) UNSIGNED NOT NULL,
    maximum_price DECIMAL(14,4) UNSIGNED NOT NULL,
    record_count INT UNSIGNED NOT NULL,
    calculated_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY market_indices_product_date_index (product_id, calculated_at),
    CONSTRAINT market_indices_product_id_foreign FOREIGN KEY (product_id)
        REFERENCES products (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci;