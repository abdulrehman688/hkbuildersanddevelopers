-- ============================================================
-- agent_locations: where each agent was while logged in to the CRM
-- Run once in phpMyAdmin (Export a backup first).
--
-- The CRM page sends a point every few minutes while an agent is logged in
-- (source = web). The Android app (phase 4) also sends points while the app
-- is in the background (source = app); device_point_id stops duplicates when
-- the app re-sends points it saved while offline.
-- ============================================================

CREATE TABLE IF NOT EXISTS agent_locations (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED  NOT NULL,
    lat             DECIMAL(10,7) NOT NULL,
    lng             DECIMAL(10,7) NOT NULL,
    accuracy_m      INT UNSIGNED  NULL,
    battery_pct     TINYINT UNSIGNED NULL,
    source          ENUM('web','app') NOT NULL DEFAULT 'web',
    device_point_id VARCHAR(64)   NULL,
    recorded_at     DATETIME      NOT NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_agent_locations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_agent_locations_device (user_id, device_point_id),
    INDEX idx_agent_locations_user (user_id, recorded_at),
    INDEX idx_agent_locations_time (recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
