-- ============================================================
-- CRM System Database Schema
-- Engine: MySQL 8.0+
-- Charset: utf8mb4
-- ============================================================


-- ============================================================
-- USERS (Admins + Agents share this table, role differentiates)
-- ============================================================
CREATE TABLE users (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100)        NOT NULL,
    email        VARCHAR(191)        NOT NULL UNIQUE,
    password     VARCHAR(255)        NOT NULL,           -- bcrypt hash
    role         ENUM('admin','agent') NOT NULL DEFAULT 'agent',
    status       ENUM('active','suspended') NOT NULL DEFAULT 'active',
    failed_logins TINYINT UNSIGNED   NOT NULL DEFAULT 0,
    locked_until  DATETIME           NULL,               -- brute-force lock
    remember_token VARCHAR(100)      NULL,
    created_at   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- LEAD SOURCES  (Admin configurable)
-- ============================================================
CREATE TABLE lead_sources (
    id    TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name  VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO lead_sources (name) VALUES
    ('Facebook Ads'),
    ('Google Ads'),
    ('Website Form'),
    ('Referral'),
    ('Walk-in'),
    ('CSV Import'),
    ('Manual Entry'),
    ('Other');

-- ============================================================
-- LEAD STATUSES  (Admin configurable pipeline stages)
-- ============================================================
CREATE TABLE lead_statuses (
    id         TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(60)  NOT NULL UNIQUE,
    color_hex  CHAR(7)      NOT NULL DEFAULT '#6B7280',  -- tailwind gray
    sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
    is_closed  TINYINT(1)   NOT NULL DEFAULT 0           -- 1 = terminal stage (Won/Lost/Dead)
) ENGINE=InnoDB;

INSERT INTO lead_statuses (name, color_hex, sort_order, is_closed) VALUES
    ('New',         '#3B82F6', 1, 0),   -- blue
    ('Contacted',   '#8B5CF6', 2, 0),   -- purple
    ('Qualified',   '#F59E0B', 3, 0),   -- amber
    ('Proposal',    '#EC4899', 4, 0),   -- pink
    ('Negotiation', '#F97316', 5, 0),   -- orange
    ('Won',         '#10B981', 6, 1),   -- green
    ('Lost',        '#EF4444', 7, 1),   -- red
    ('Dead',        '#6B7280', 8, 1);   -- gray

-- ============================================================
-- LEADS
-- ============================================================
CREATE TABLE leads (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Contact info
    name            VARCHAR(150)      NOT NULL,
    email           VARCHAR(191)      NULL,
    phone           VARCHAR(30)       NULL,
    company         VARCHAR(150)      NULL,
    country         VARCHAR(80)       NULL,

    -- Classification
    source_id       TINYINT UNSIGNED  NULL,
    status_id       TINYINT UNSIGNED  NOT NULL DEFAULT 1,
    priority        ENUM('hot','warm','cold') NOT NULL DEFAULT 'warm',

    -- Notes entered at creation
    initial_notes   TEXT              NULL,

    -- Assignment
    assigned_to     INT UNSIGNED      NULL,               -- FK -> users.id (agent)
    claimed_at      DATETIME          NULL,

    -- Soft delete
    deleted_at      DATETIME          NULL,

    -- Tracking
    created_by      INT UNSIGNED      NOT NULL,           -- FK -> users.id (admin who added)
    created_at      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (source_id)   REFERENCES lead_sources(id)  ON DELETE SET NULL,
    FOREIGN KEY (status_id)   REFERENCES lead_statuses(id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_to) REFERENCES users(id)         ON DELETE SET NULL,
    FOREIGN KEY (created_by)  REFERENCES users(id)         ON DELETE RESTRICT,

    INDEX idx_assigned_to (assigned_to),
    INDEX idx_status_id   (status_id),
    INDEX idx_deleted_at  (deleted_at),
    INDEX idx_created_at  (created_at)
) ENGINE=InnoDB;

-- ============================================================
-- LEAD ACTIVITIES  (Full timeline: status changes, notes, calls)
-- ============================================================
CREATE TABLE lead_activities (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lead_id     INT UNSIGNED    NOT NULL,
    user_id     INT UNSIGNED    NOT NULL,               -- who did the action
    type        ENUM(
                    'note',         -- free-text note
                    'status_change',-- status updated
                    'claim',        -- agent claimed the lead
                    'reassign',     -- admin reassigned
                    'call',         -- logged a call
                    'email',        -- logged an email
                    'followup_set', -- follow-up scheduled
                    'csv_import'    -- created via CSV
                ) NOT NULL DEFAULT 'note',
    note        TEXT            NULL,
    meta        JSON            NULL,                   -- e.g. {"from_status":1,"to_status":2}
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,

    INDEX idx_lead_id   (lead_id),
    INDEX idx_created_at(created_at)
) ENGINE=InnoDB;

-- ============================================================
-- FOLLOW-UPS  (Scheduled callbacks / reminders per lead)
-- ============================================================
CREATE TABLE follow_ups (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lead_id      INT UNSIGNED  NOT NULL,
    agent_id     INT UNSIGNED  NOT NULL,
    scheduled_at DATETIME      NOT NULL,
    note         TEXT          NULL,
    is_done      TINYINT(1)    NOT NULL DEFAULT 0,
    done_at      DATETIME      NULL,
    created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (lead_id)  REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_agent_scheduled (agent_id, scheduled_at),
    INDEX idx_is_done         (is_done)
) ENGINE=InnoDB;

-- ============================================================
-- CSV IMPORT BATCHES  (Track each upload for audit)
-- ============================================================
CREATE TABLE import_batches (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uploaded_by   INT UNSIGNED  NOT NULL,
    filename      VARCHAR(255)  NOT NULL,
    total_rows    INT UNSIGNED  NOT NULL DEFAULT 0,
    imported      INT UNSIGNED  NOT NULL DEFAULT 0,
    skipped       INT UNSIGNED  NOT NULL DEFAULT 0,   -- duplicates / invalid
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- AUDIT LOG  (Every sensitive action ever taken)
-- ============================================================
CREATE TABLE audit_log (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED  NULL,                    -- NULL if unauthenticated
    action      VARCHAR(100)  NOT NULL,                -- e.g. 'login', 'lead.delete'
    target_type VARCHAR(50)   NULL,                    -- e.g. 'lead', 'user'
    target_id   INT UNSIGNED  NULL,
    ip_address  VARCHAR(45)   NULL,                    -- supports IPv6
    user_agent  VARCHAR(300)  NULL,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user_id   (user_id),
    INDEX idx_action    (action),
    INDEX idx_created_at(created_at)
) ENGINE=InnoDB;

-- ============================================================
-- DEFAULT ADMIN USER
-- Password: Admin@1234  (bcrypt — CHANGE IMMEDIATELY after first login)
-- ============================================================
INSERT INTO users (name, email, password, role) VALUES (
    'Super Admin',
    'admin@crm.local',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Admin@1234
    'admin'
);
