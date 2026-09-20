-- Units section migration
-- Run this once on both local (phpMyAdmin) and Hostinger (phpMyAdmin)

CREATE TABLE IF NOT EXISTS builder_units (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    builder_id       INT NOT NULL,
    project_id       INT NOT NULL,
    unit_number      VARCHAR(50)    NOT NULL,
    block_number     VARCHAR(50)    DEFAULT NULL,
    category         VARCHAR(100)   DEFAULT NULL,
    plot_size        VARCHAR(50)    DEFAULT NULL,
    total_cost       DECIMAL(15,2)  NOT NULL DEFAULT 0,
    down_payment     DECIMAL(15,2)  NOT NULL DEFAULT 0,
    commission_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    commission_status ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid',
    notes            TEXT           DEFAULT NULL,
    created_by       INT            DEFAULT NULL,
    created_at       TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (builder_id)  REFERENCES builders(id)          ON DELETE CASCADE,
    FOREIGN KEY (project_id)  REFERENCES builder_projects(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
