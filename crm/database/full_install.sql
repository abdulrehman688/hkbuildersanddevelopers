-- ============================================================
-- HK Builders CRM — Full Install (Fresh Database)
-- Run this in phpMyAdmin on Hostinger
-- Order: schema → v2 migration → v3 migration → seed data
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. SCHEMA (Base Tables)
-- ============================================================

CREATE TABLE IF NOT EXISTS users (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100)        NOT NULL,
    email        VARCHAR(191)        NOT NULL UNIQUE,
    password     VARCHAR(255)        NOT NULL,
    role         ENUM('admin','sales_manager','agent') NOT NULL DEFAULT 'agent',
    status       ENUM('active','suspended') NOT NULL DEFAULT 'active',
    failed_logins TINYINT UNSIGNED   NOT NULL DEFAULT 0,
    locked_until  DATETIME           NULL,
    remember_token VARCHAR(100)      NULL,
    phone           VARCHAR(30)      NULL,
    address         VARCHAR(255)     NULL,
    cnic            VARCHAR(20)      NULL,
    guardian_phone  VARCHAR(30)      NULL,
    designation     VARCHAR(100)     NULL,
    base_salary     DECIMAL(12,2)    NOT NULL DEFAULT 0.00,
    commission_rate DECIMAL(5,2)     NOT NULL DEFAULT 0.00,
    team_id         SMALLINT UNSIGNED NULL,
    created_at   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lead_sources (
    id    TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name  VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO lead_sources (name) VALUES
    ('Facebook Ads'),('Google Ads'),('Website Form'),('Referral'),
    ('Walk-in'),('CSV Import'),('Manual Entry'),('Other');

CREATE TABLE IF NOT EXISTS lead_statuses (
    id         TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(60)  NOT NULL UNIQUE,
    color_hex  CHAR(7)      NOT NULL DEFAULT '#6B7280',
    sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
    is_closed  TINYINT(1)   NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO lead_statuses (name, color_hex, sort_order, is_closed) VALUES
    ('New',         '#3B82F6', 1, 0),
    ('Contacted',   '#8B5CF6', 2, 0),
    ('Qualified',   '#F59E0B', 3, 0),
    ('Proposal',    '#EC4899', 4, 0),
    ('Negotiation', '#F97316', 5, 0),
    ('Won',         '#10B981', 6, 1),
    ('Lost',        '#EF4444', 7, 1),
    ('Dead',        '#6B7280', 8, 1);

CREATE TABLE IF NOT EXISTS leads (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150)      NULL,
    email           VARCHAR(191)      NULL,
    phone           VARCHAR(30)       NULL,
    company         VARCHAR(150)      NULL,
    country         VARCHAR(80)       NULL,
    address         VARCHAR(255)      NULL,
    project         VARCHAR(150)      NULL,
    investment_amount DECIMAL(15,2)   NULL,
    unit            VARCHAR(80)       NULL,
    category        VARCHAR(80)       NULL,
    source_id       TINYINT UNSIGNED  NULL,
    status_id       TINYINT UNSIGNED  NOT NULL DEFAULT 1,
    priority        ENUM('hot','warm','cold') NOT NULL DEFAULT 'warm',
    initial_notes   TEXT              NULL,
    assigned_to     INT UNSIGNED      NULL,
    claimed_at      DATETIME          NULL,
    deleted_at      DATETIME          NULL,
    created_by      INT UNSIGNED      NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lead_activities (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lead_id     INT UNSIGNED    NOT NULL,
    user_id     INT UNSIGNED    NOT NULL,
    type        ENUM('note','status_change','claim','reassign','call','email','followup_set','csv_import') NOT NULL DEFAULT 'note',
    note        TEXT            NULL,
    meta        JSON            NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_lead_id   (lead_id),
    INDEX idx_created_at(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS follow_ups (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS import_batches (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uploaded_by   INT UNSIGNED  NOT NULL,
    filename      VARCHAR(255)  NOT NULL,
    total_rows    INT UNSIGNED  NOT NULL DEFAULT 0,
    imported      INT UNSIGNED  NOT NULL DEFAULT 0,
    skipped       INT UNSIGNED  NOT NULL DEFAULT 0,
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_log (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED  NULL,
    action      VARCHAR(100)  NOT NULL,
    target_type VARCHAR(50)   NULL,
    target_id   INT UNSIGNED  NULL,
    detail      VARCHAR(500)  NULL,
    ip_address  VARCHAR(45)   NULL,
    user_agent  VARCHAR(300)  NULL,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id   (user_id),
    INDEX idx_action    (action),
    INDEX idx_created_at(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 2. V2 TABLES (Teams, Clients, Salaries, Payments, Expenses, Income)
-- ============================================================

CREATE TABLE IF NOT EXISTS teams (
    id               SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(80)   NOT NULL UNIQUE,
    sales_manager_id INT UNSIGNED  NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sales_manager_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_sales_manager (sales_manager_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS clients (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lead_id        INT UNSIGNED   NULL,
    name           VARCHAR(150)   NULL,
    address        VARCHAR(255)   NULL,
    contact_no     VARCHAR(30)    NULL,
    project        VARCHAR(150)   NULL,
    block          VARCHAR(80)    NULL,
    unit_no        VARCHAR(80)    NULL,
    category       VARCHAR(80)    NULL,
    booking_amount DECIMAL(15,2)  NULL,
    agent_id       INT UNSIGNED   NULL,
    source_id      TINYINT UNSIGNED NULL,
    file_status    ENUM('mature','immature') NOT NULL DEFAULT 'mature',
    file_flag_reason VARCHAR(255) NULL,
    flagged_by     INT UNSIGNED   NULL,
    flagged_at     DATETIME       NULL,
    created_by     INT UNSIGNED   NOT NULL,
    created_at     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id)    REFERENCES leads(id)        ON DELETE SET NULL,
    FOREIGN KEY (agent_id)   REFERENCES users(id)        ON DELETE SET NULL,
    FOREIGN KEY (source_id)  REFERENCES lead_sources(id) ON DELETE SET NULL,
    FOREIGN KEY (flagged_by) REFERENCES users(id)        ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id)        ON DELETE RESTRICT,
    INDEX idx_agent       (agent_id),
    INDEX idx_file_status (file_status),
    INDEX idx_created_at  (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS salaries (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id           INT UNSIGNED  NOT NULL,
    period_month      DATE          NOT NULL,
    base_salary       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    commission_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    adjustment        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_amount      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    is_paid           TINYINT(1)    NOT NULL DEFAULT 0,
    paid_at           DATETIME      NULL,
    is_locked         TINYINT(1)    NOT NULL DEFAULT 0,
    notes             VARCHAR(255)  NULL,
    created_by        INT UNSIGNED  NOT NULL,
    created_at        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    UNIQUE KEY uq_user_month (user_id, period_month),
    INDEX idx_period  (period_month),
    INDEX idx_is_paid (is_paid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS client_payments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id       INT UNSIGNED   NOT NULL,
    payment_type    ENUM('booking') NOT NULL DEFAULT 'booking',
    expected_amount DECIMAL(15,2)  NULL,
    paid_amount     DECIMAL(15,2)  NOT NULL DEFAULT 0.00,
    payment_date    DATE           NULL,
    due_date        DATE           NULL,
    is_paid         TINYINT(1)     NOT NULL DEFAULT 0,
    pending_reason  ENUM('immature_file','no_funds','other') NULL,
    notes           VARCHAR(255)   NULL,
    created_by      INT UNSIGNED   NOT NULL,
    created_at      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id)  REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)   ON DELETE RESTRICT,
    INDEX idx_client  (client_id),
    INDEX idx_is_paid (is_paid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expenses (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category     ENUM('marketing','office','other') NOT NULL DEFAULT 'other',
    amount       DECIMAL(12,2) NOT NULL,
    description  VARCHAR(255)  NULL,
    expense_date DATE          NOT NULL,
    created_by   INT UNSIGNED  NOT NULL,
    created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_category     (category),
    INDEX idx_expense_date (expense_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS income (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source        ENUM('booking','installment','other') NOT NULL DEFAULT 'booking',
    client_id     INT UNSIGNED   NULL,
    amount        DECIMAL(15,2)  NOT NULL,
    description   VARCHAR(255)   NULL,
    received_date DATE           NOT NULL,
    created_by    INT UNSIGNED   NOT NULL,
    created_at    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id)  REFERENCES clients(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id)   ON DELETE RESTRICT,
    INDEX idx_source        (source),
    INDEX idx_received_date (received_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 3. V3 TABLES (Notices & Tasks)
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

CREATE TABLE IF NOT EXISTS notice_completions (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    notice_id      INT UNSIGNED NOT NULL,
    user_id        INT UNSIGNED NOT NULL,
    marked_done_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_notice_user (notice_id, user_id),
    FOREIGN KEY (notice_id) REFERENCES notices(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- 4. SEED DATA
-- ============================================================

INSERT INTO users (id, name, email, password, role, status,
                   phone, address, cnic, guardian_phone, designation,
                   base_salary, commission_rate, created_at) VALUES
(1, 'Super Admin', 'admin@hkbuilders.com',
 '$2y$12$mmgT4sqwEl.carDuA8Cim.bQ262QqryauBYrH4qHWXS..T/bl0adS',
 'admin', 'active',
 '0300-1111111', 'HK Builders Office, Karachi', NULL, NULL, 'Administrator',
 0, 0, '2026-01-01 09:00:00'),
(2, 'Usman Tariq', 'usman@hkbuilders.com',
 '$2y$12$mmgT4sqwEl.carDuA8Cim.bQ262QqryauBYrH4qHWXS..T/bl0adS',
 'sales_manager', 'active',
 '0321-2222222', 'Block 14, North Nazimabad, Karachi', '42101-1234567-1', '0300-9998888', 'Senior Sales Manager',
 65000, 2.00, '2026-01-05 10:00:00'),
(3, 'Hina Baig', 'hina@hkbuilders.com',
 '$2y$12$mmgT4sqwEl.carDuA8Cim.bQ262QqryauBYrH4qHWXS..T/bl0adS',
 'sales_manager', 'active',
 '0333-3333333', 'Gulshan-e-Iqbal Block 6, Karachi', '42201-9876543-2', '0321-7776666', 'Sales Manager',
 55000, 1.75, '2026-01-08 10:00:00'),
(4, 'Ali Hassan', 'ali@hkbuilders.com',
 '$2y$12$mmgT4sqwEl.carDuA8Cim.bQ262QqryauBYrH4qHWXS..T/bl0adS',
 'agent', 'active',
 '0312-4444444', 'Nazimabad No. 2, Karachi', '42101-3456789-3', '0300-5554444', 'Senior Sales Executive',
 35000, 1.50, '2026-01-10 10:00:00'),
(5, 'Sara Ahmed', 'sara@hkbuilders.com',
 '$2y$12$mmgT4sqwEl.carDuA8Cim.bQ262QqryauBYrH4qHWXS..T/bl0adS',
 'agent', 'active',
 '0345-5555555', 'Liaquatabad, Karachi', '42201-4567890-4', '0333-4443333', 'Sales Executive',
 30000, 1.25, '2026-01-15 10:00:00'),
(6, 'Bilal Mirza', 'bilal@hkbuilders.com',
 '$2y$12$mmgT4sqwEl.carDuA8Cim.bQ262QqryauBYrH4qHWXS..T/bl0adS',
 'agent', 'active',
 '0333-6666666', 'Gulberg, Karachi', '42301-5678901-5', '0321-3332222', 'Sales Executive',
 28000, 1.00, '2026-02-01 10:00:00'),
(7, 'Fatima Malik', 'fatima@hkbuilders.com',
 '$2y$12$mmgT4sqwEl.carDuA8Cim.bQ262QqryauBYrH4qHWXS..T/bl0adS',
 'agent', 'active',
 '0300-7777777', 'DHA Phase 5, Karachi', '42401-6789012-6', '0312-2221111', 'Sales Executive',
 30000, 1.25, '2026-02-05 10:00:00'),
(8, 'Kamran Shah', 'kamran@hkbuilders.com',
 '$2y$12$mmgT4sqwEl.carDuA8Cim.bQ262QqryauBYrH4qHWXS..T/bl0adS',
 'agent', 'active',
 '0321-8888888', 'Korangi, Karachi', '42501-7890123-7', '0300-1110000', 'Junior Sales Executive',
 25000, 1.00, '2026-02-10 10:00:00'),
(9, 'Zainab Qureshi', 'zainab@hkbuilders.com',
 '$2y$12$mmgT4sqwEl.carDuA8Cim.bQ262QqryauBYrH4qHWXS..T/bl0adS',
 'agent', 'suspended',
 '0312-9999999', 'Saddar, Karachi', '42601-8901234-8', '0345-0009999', 'Sales Executive',
 28000, 1.00, '2026-03-01 10:00:00');

INSERT INTO leads (name, email, phone, address, project, investment_amount,
                   unit, category, source_id, status_id, priority,
                   initial_notes, assigned_to, claimed_at, created_by, created_at) VALUES
('Ahmed Farooq', 'ahmed.f@gmail.com', '0300-1001001',
 'Block 5, Clifton, Karachi', 'Falaknaz Hills View', 4500000,
 '2BHK Apartment', 'Residential', 1, 6, 'hot',
 'Very interested, came through Facebook. Ready to book.',
 4, '2026-02-01 10:00:00', 1, '2026-02-01 09:00:00'),
('Nasreen Bano', NULL, '0321-2002002',
 'Gulshan Block 13, Karachi', 'Falaknaz Overseas Block', 6000000,
 '3BHK Apartment', 'Residential', 4, 6, 'hot',
 'Overseas client referred by existing buyer.',
 5, '2026-02-10 11:00:00', 1, '2026-02-10 09:00:00'),
('Tariq Mehmood', 'tariq.m@hotmail.com', '0333-3003003',
 'North Karachi Sector 11', 'United Palm Greens', 3200000,
 '120 Sq Yd Plot', 'Plot', 2, 6, 'hot',
 'Google ad lead. Came to office twice.',
 4, '2026-03-05 09:30:00', 1, '2026-03-05 09:00:00'),
('Sobia Raza', 'sobia.r@yahoo.com', '0312-4004004',
 'PECHS Block 6, Karachi', 'Falaknaz Wonder City', 8000000,
 'Commercial Unit', 'Commercial', 1, 6, 'hot',
 'Wants commercial space for office. High budget.',
 7, '2026-03-12 10:00:00', 1, '2026-03-12 09:00:00'),
('Imran Siddiqui', 'imran.s@gmail.com', '0345-5005005',
 'Landhi, Karachi', 'Falaknaz Hills View', 3800000,
 '2BHK Apartment', 'Residential', 1, 5, 'hot',
 'Negotiating on price. Very close to closing.',
 4, '2026-04-01 10:00:00', 1, '2026-04-01 09:00:00'),
('Amna Khalid', NULL, '0300-6006006',
 'Malir City, Karachi', 'United Palm Greens', 2500000,
 '80 Sq Yd Plot', 'Plot', 3, 5, 'hot',
 'Website form inquiry. Strong interest.',
 5, '2026-04-05 10:00:00', 1, '2026-04-05 09:00:00'),
('Rehan Butt', 'rehan.b@gmail.com', '0321-7007007',
 'FB Area, Karachi', 'Falaknaz Overseas Block', 5500000,
 '3BHK Apartment', 'Residential', 2, 4, 'hot',
 'Has seen the brochure. Wants payment plan.',
 6, '2026-04-10 10:00:00', 1, '2026-04-10 09:00:00'),
('Saima Nawaz', 'saima.n@outlook.com', '0333-8008008',
 'Korangi Industrial Area', 'Falaknaz Wonder City', 4200000,
 '2BHK Apartment', 'Residential', 4, 4, 'warm',
 'Referral from Ahmed Farooq. Interested in same project.',
 7, '2026-04-15 10:00:00', 1, '2026-04-15 09:00:00'),
('Junaid Alam', NULL, '0312-9009009',
 NULL, 'Falaknaz Hills View', 3500000,
 '2BHK Apartment', 'Residential', 1, 3, 'warm',
 'Facebook ad. Asking good questions about location.',
 4, '2026-05-01 10:00:00', 1, '2026-05-01 09:00:00'),
('Mariam Aziz', 'mariam.a@gmail.com', '0345-1010101',
 'Gulberg Town, Karachi', 'United Palm Greens', 4800000,
 '160 Sq Yd Plot', 'Plot', 2, 3, 'warm',
 'Google ad. Has budget confirmed.',
 5, '2026-05-05 10:00:00', 1, '2026-05-05 09:00:00'),
('Faisal Qazi', 'faisal.q@hotmail.com', '0300-1111101',
 'Model Colony, Karachi', 'Falaknaz Wonder City', 7500000,
 'Commercial Plot', 'Commercial', 1, 3, 'hot',
 'Looking for commercial investment. High potential.',
 8, '2026-05-10 10:00:00', 1, '2026-05-10 09:00:00'),
('Huma Sheikh', NULL, '0321-1212120',
 NULL, 'Falaknaz Hills View', 3000000,
 '1BHK Apartment', 'Residential', 1, 2, 'warm',
 'First call done. Will call back next week.',
 6, '2026-05-15 10:00:00', 1, '2026-05-15 09:00:00'),
('Waseem Akram', 'waseem.a@gmail.com', '0333-1313130',
 'Surjani Town, Karachi', 'Falaknaz Overseas Block', 5000000,
 '3BHK Apartment', 'Residential', 4, 2, 'warm',
 'Contacted twice. Seems interested but wants more info.',
 7, '2026-05-18 10:00:00', 1, '2026-05-18 09:00:00'),
('Rabia Farhan', 'rabia.f@yahoo.com', '0312-1414140',
 'New Karachi, Karachi', 'United Palm Greens', 2200000,
 '80 Sq Yd Plot', 'Plot', 3, 2, 'cold',
 'Website inquiry. Not very responsive.',
 8, '2026-06-01 10:00:00', 1, '2026-06-01 09:00:00'),
('Shahid Nawaz', NULL, '0345-1515150',
 NULL, 'Falaknaz Hills View', NULL,
 NULL, 'Residential', 1, 1, 'warm',
 'Fresh Facebook lead.',
 4, '2026-06-10 10:00:00', 1, '2026-06-10 09:00:00'),
('Nadia Islam', 'nadia.i@gmail.com', '0300-1616160',
 'Gulistan-e-Johar Block 15', 'Falaknaz Wonder City', 4000000,
 '2BHK Apartment', 'Residential', 2, 1, 'hot',
 'Google ad. Very specific about Falaknaz Wonder City.',
 5, '2026-06-12 10:00:00', 1, '2026-06-12 09:00:00'),
('Adeel Hussain', NULL, '0321-1717170',
 NULL, NULL, 3000000,
 NULL, NULL, 1, 1, 'cold',
 'Just asked for general info.',
 6, '2026-06-14 10:00:00', 1, '2026-06-14 09:00:00'),
('Khalid Mehmood', 'khalid.m@gmail.com', '0333-1818180',
 'Orangi Town, Karachi', 'Falaknaz Hills View', 2000000,
 '1BHK Apartment', 'Residential', 1, 7, 'cold',
 'Budget too low. Could not match expectations.',
 7, '2026-03-20 10:00:00', 1, '2026-03-20 09:00:00'),
('Rukhsar Parveen', NULL, '0312-1919190',
 NULL, 'United Palm Greens', 1500000,
 '80 Sq Yd Plot', 'Plot', 4, 7, 'cold',
 'Went with a competitor.',
 8, '2026-04-02 10:00:00', 1, '2026-04-02 09:00:00'),
('Zafar Iqbal', 'zafar.i@yahoo.com', '0345-2020200',
 'Baldia Town, Karachi', NULL, NULL,
 NULL, NULL, 3, 8, 'cold',
 'Phone switched off. Multiple attempts failed.',
 6, '2026-04-20 10:00:00', 1, '2026-04-20 09:00:00'),
('Unknown', NULL, '0300-2121210',
 NULL, 'Falaknaz Overseas Block', 5500000,
 '3BHK', 'Residential', 1, 1, 'hot',
 'Hot Facebook lead. Needs immediate follow-up.',
 NULL, NULL, 1, '2026-06-20 09:00:00'),
(NULL, NULL, '0321-2222220',
 NULL, 'Falaknaz Hills View', NULL,
 NULL, 'Residential', 1, 1, 'warm',
 'Phone-only lead from Facebook ad.',
 NULL, NULL, 1, '2026-06-21 09:00:00'),
('Pervez Anwar', 'pervez.a@gmail.com', '0333-2323230',
 'Lyari, Karachi', 'United Palm Greens', 3500000,
 '120 Sq Yd Plot', 'Plot', 2, 1, 'warm',
 'Google ad inquiry.',
 NULL, NULL, 1, '2026-06-22 09:00:00'),
('Shaista Noor', NULL, '0312-2424240',
 'Orangi Town, Karachi', 'Falaknaz Wonder City', 4500000,
 '2BHK Apartment', 'Residential', 4, 1, 'hot',
 'Referral from existing client.',
 NULL, NULL, 1, '2026-06-23 09:00:00'),
(NULL, NULL, '0345-2525250',
 NULL, NULL, NULL,
 NULL, NULL, 1, 1, 'cold',
 'Phone-only — source unknown.',
 NULL, NULL, 1, '2026-06-24 09:00:00'),
('Mohsin Raza', 'mohsin.r@hotmail.com', '0300-2626260',
 'Gulshan Block 7, Karachi', 'Falaknaz Hills View', 4000000,
 '2BHK Apartment', 'Residential', 3, 1, 'warm',
 'Website contact form.',
 NULL, NULL, 1, '2026-06-25 09:00:00'),
('Asma Bibi', NULL, '0321-2727270',
 NULL, 'Falaknaz Overseas Block', 6500000,
 '3BHK Apartment', 'Residential', 1, 1, 'hot',
 'Interested in overseas block specifically.',
 NULL, NULL, 1, '2026-06-26 09:00:00'),
('Tariq Zaman', 'tariq.z@gmail.com', '0333-2828280',
 'Malir Halt, Karachi', 'United Palm Greens', 2800000,
 '120 Sq Yd Plot', 'Plot', 2, 2, 'warm',
 'Contacted once. Wants site visit.',
 8, '2026-06-15 10:00:00', 1, '2026-06-15 09:00:00'),
('Lubna Farooq', NULL, '0312-2929290',
 'Korangi, Karachi', 'Falaknaz Wonder City', 3900000,
 '2BHK Apartment', 'Residential', 4, 3, 'warm',
 'Referral. Seems qualified.',
 7, '2026-06-16 10:00:00', 1, '2026-06-16 09:00:00'),
('Rizwan Ali', 'rizwan.ali@gmail.com', '0345-3030300',
 'Gulistan-e-Johar, Karachi', 'Falaknaz Hills View', 4200000,
 '2BHK Apartment', 'Residential', 1, 4, 'hot',
 'Facebook ad. Has visited site once.',
 8, '2026-06-17 10:00:00', 1, '2026-06-17 09:00:00');

INSERT INTO lead_activities (lead_id, user_id, type, note, created_at) VALUES
(1, 4, 'claim',        'Lead claimed from pool.',                                    '2026-02-01 10:00:00'),
(1, 4, 'status_change','Status changed to Contacted.',                               '2026-02-03 11:00:00'),
(1, 4, 'note',         'Called Ahmed. Very interested. Wants to visit site.',        '2026-02-03 11:05:00'),
(1, 4, 'status_change','Status changed to Qualified.',                               '2026-02-08 10:00:00'),
(1, 4, 'note',         'Site visit done. Client liked the project. Shared brochure.','2026-02-08 10:10:00'),
(1, 4, 'status_change','Status changed to Proposal.',                                '2026-02-12 10:00:00'),
(1, 4, 'status_change','Status changed to Negotiation.',                             '2026-02-16 10:00:00'),
(1, 4, 'note',         'Negotiated price. Client agreed on 45 lakh.',                '2026-02-16 10:30:00'),
(1, 4, 'status_change','Status changed to Won.',                                     '2026-02-20 10:00:00'),
(1, 4, 'note',         'Deal closed! Token money received.',                         '2026-02-20 10:05:00'),
(2, 5, 'claim',        'Lead claimed from pool.',                                    '2026-02-10 11:00:00'),
(2, 5, 'status_change','Status changed to Contacted.',                               '2026-02-11 10:00:00'),
(2, 5, 'note',         'Overseas client. Communication via WhatsApp.',               '2026-02-11 10:10:00'),
(2, 5, 'status_change','Status changed to Qualified.',                               '2026-02-18 09:00:00'),
(2, 5, 'status_change','Status changed to Won.',                                     '2026-03-01 10:00:00'),
(2, 5, 'note',         'Booking confirmed. Full payment overseas transfer.',         '2026-03-01 10:15:00'),
(5, 4, 'claim',        'Lead claimed from pool.',                                    '2026-04-01 10:00:00'),
(5, 4, 'status_change','Moved to Contacted.',                                        '2026-04-02 10:00:00'),
(5, 4, 'status_change','Moved to Qualified.',                                        '2026-04-08 10:00:00'),
(5, 4, 'status_change','Moved to Proposal.',                                         '2026-04-14 10:00:00'),
(5, 4, 'status_change','Moved to Negotiation.',                                      '2026-04-20 10:00:00'),
(5, 4, 'note',         'Client wants a 10% discount. Checking with admin.',         '2026-04-20 10:10:00'),
(18, 7, 'claim',        'Lead claimed.',                                             '2026-03-20 10:00:00'),
(18, 7, 'status_change','Moved to Contacted.',                                       '2026-03-21 10:00:00'),
(18, 7, 'note',         'Budget is 20 lakh, too low for available units.',           '2026-03-21 10:10:00'),
(18, 7, 'status_change','Moved to Lost.',                                            '2026-03-25 10:00:00'),
(18, 7, 'note',         'Could not match budget. Closed as lost.',                  '2026-03-25 10:05:00');

INSERT INTO follow_ups (lead_id, agent_id, scheduled_at, note, is_done, done_at, created_at) VALUES
(5,  4, '2026-04-25 10:00:00', 'Call to discuss discount decision.',         1, '2026-04-25 10:15:00', '2026-04-20 10:30:00'),
(9,  4, '2026-05-05 11:00:00', 'Send payment plan details.',                 1, '2026-05-05 11:10:00', '2026-05-03 09:00:00'),
(13, 7, '2026-05-20 10:00:00', 'Follow up on brochure review.',              1, '2026-05-20 10:20:00', '2026-05-18 11:00:00'),
(6,  5, '2026-06-30 10:00:00', 'Call to confirm booking intention.',         0, NULL, '2026-06-25 09:00:00'),
(7,  6, '2026-06-30 11:00:00', 'Share updated payment plan.',                0, NULL, '2026-06-25 10:00:00'),
(10, 5, '2026-07-01 09:00:00', 'Follow up after site visit.',                0, NULL, '2026-06-26 09:00:00'),
(11, 8, '2026-07-02 10:00:00', 'Send commercial unit brochure.',             0, NULL, '2026-06-26 10:00:00'),
(16, 5, '2026-07-03 10:00:00', 'First proper follow-up call.',               0, NULL, '2026-06-27 09:00:00'),
(28, 7, '2026-07-03 11:00:00', 'Schedule site visit.',                       0, NULL, '2026-06-27 10:00:00');

-- ============================================================
-- Done! Login: admin@hkbuilders.com / Admin@1234
-- ============================================================
