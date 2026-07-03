-- ============================================================
-- CRM v3 Migration — Notices & Tasks
-- Creates: notices, notice_completions
-- Run once on existing database.
-- ============================================================

CREATE TABLE IF NOT EXISTS notices (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type        ENUM('announcement','task') NOT NULL DEFAULT 'announcement',
    title       VARCHAR(255) NOT NULL,
    message     TEXT         NOT NULL,
    attachment  VARCHAR(500) NULL,
    created_by  INT UNSIGNED NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_created_at (created_at),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tracks per-user task completion
CREATE TABLE IF NOT EXISTS notice_completions (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    notice_id      INT UNSIGNED NOT NULL,
    user_id        INT UNSIGNED NOT NULL,
    marked_done_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_notice_user (notice_id, user_id),
    FOREIGN KEY (notice_id) REFERENCES notices(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
