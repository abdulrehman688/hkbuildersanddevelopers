-- ============================================================
-- contact_logs: every phone call between an agent and a lead or client
-- Run once in phpMyAdmin (Export a backup first).
--
-- A call tapped in the CRM is saved straight away (source = app_button).
-- The agent then picks the outcome and talk time in the popup.
-- The Android app (phase 4) reads the phone's call log and fills the real
-- talk time (source = phone_log); device_call_id stops double counting.
-- duration_sec NULL means the talk time is not known yet.
-- ============================================================

CREATE TABLE IF NOT EXISTS contact_logs (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lead_id        INT UNSIGNED  NULL,
    client_id      INT UNSIGNED  NULL,
    user_id        INT UNSIGNED  NOT NULL,
    phone          VARCHAR(30)   NULL,
    direction      ENUM('outgoing','incoming','missed') NOT NULL DEFAULT 'outgoing',
    started_at     DATETIME      NOT NULL,
    duration_sec   INT UNSIGNED  NULL,
    outcome        ENUM('answered','no_answer','busy','switched_off','wrong_number') NULL,
    note           VARCHAR(500)  NULL,
    source         ENUM('app_button','phone_log','manual') NOT NULL DEFAULT 'app_button',
    device_call_id VARCHAR(64)   NULL,
    created_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_contact_logs_lead   FOREIGN KEY (lead_id)   REFERENCES leads(id)   ON DELETE CASCADE,
    CONSTRAINT fk_contact_logs_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    CONSTRAINT fk_contact_logs_user   FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    UNIQUE KEY uq_contact_logs_device (user_id, device_call_id),
    INDEX idx_contact_logs_lead    (lead_id, started_at),
    INDEX idx_contact_logs_client  (client_id, started_at),
    INDEX idx_contact_logs_user    (user_id, started_at),
    INDEX idx_contact_logs_started (started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
