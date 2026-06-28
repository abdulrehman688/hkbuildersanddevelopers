-- ============================================================
-- CRM Seed Data — HK Builders & Developers
-- Run AFTER schema.sql and migration_v2.sql
-- Clears existing test data and re-inserts fresh records.
-- ============================================================

-- Clear existing data (child tables first to respect foreign keys)
DELETE FROM client_payments;
DELETE FROM clients;
DELETE FROM salaries;
DELETE FROM expenses;
DELETE FROM import_batches;
DELETE FROM lead_activities;
DELETE FROM follow_ups;
DELETE FROM leads;
DELETE FROM audit_log;
DELETE FROM users;

-- Reset auto-increment counters
ALTER TABLE client_payments  AUTO_INCREMENT = 1;
ALTER TABLE clients          AUTO_INCREMENT = 1;
ALTER TABLE salaries         AUTO_INCREMENT = 1;
ALTER TABLE expenses         AUTO_INCREMENT = 1;
ALTER TABLE import_batches   AUTO_INCREMENT = 1;
ALTER TABLE lead_activities  AUTO_INCREMENT = 1;
ALTER TABLE follow_ups       AUTO_INCREMENT = 1;
ALTER TABLE leads            AUTO_INCREMENT = 1;
ALTER TABLE audit_log        AUTO_INCREMENT = 1;
ALTER TABLE users            AUTO_INCREMENT = 1;

-- ============================================================
-- USERS
-- All passwords = Test@1234
-- Hash: $2y$12$eImiTXuWVxfM37uY4JANjQ==  (bcrypt cost 12)
-- ============================================================

INSERT INTO users (id, name, email, password, role, status,
                   phone, address, cnic, guardian_phone, designation,
                   base_salary, commission_rate, created_at) VALUES

-- Admin (already exists from schema, but re-insert cleanly)
(1, 'Super Admin', 'admin@hkbuilders.com',
 '$2y$12$LiSXqjxTBMVzLIoGN3vHdeU3TKqPq7IXRQi7ggS8YRjRXD8nwVyGi',
 'admin', 'active',
 '0300-1111111', 'HK Builders Office, Karachi', NULL, NULL, 'Administrator',
 0, 0, '2026-01-01 09:00:00'),

-- Sales Managers
(2, 'Usman Tariq', 'usman@hkbuilders.com',
 '$2y$12$LiSXqjxTBMVzLIoGN3vHdeU3TKqPq7IXRQi7ggS8YRjRXD8nwVyGi',
 'sales_manager', 'active',
 '0321-2222222', 'Block 14, North Nazimabad, Karachi', '42101-1234567-1', '0300-9998888', 'Senior Sales Manager',
 65000, 2.00, '2026-01-05 10:00:00'),

(3, 'Hina Baig', 'hina@hkbuilders.com',
 '$2y$12$LiSXqjxTBMVzLIoGN3vHdeU3TKqPq7IXRQi7ggS8YRjRXD8nwVyGi',
 'sales_manager', 'active',
 '0333-3333333', 'Gulshan-e-Iqbal Block 6, Karachi', '42201-9876543-2', '0321-7776666', 'Sales Manager',
 55000, 1.75, '2026-01-08 10:00:00'),

-- Agents under Usman (Team A)
(4, 'Ali Hassan', 'ali@hkbuilders.com',
 '$2y$12$LiSXqjxTBMVzLIoGN3vHdeU3TKqPq7IXRQi7ggS8YRjRXD8nwVyGi',
 'agent', 'active',
 '0312-4444444', 'Nazimabad No. 2, Karachi', '42101-3456789-3', '0300-5554444', 'Senior Sales Executive',
 35000, 1.50, '2026-01-10 10:00:00'),

(5, 'Sara Ahmed', 'sara@hkbuilders.com',
 '$2y$12$LiSXqjxTBMVzLIoGN3vHdeU3TKqPq7IXRQi7ggS8YRjRXD8nwVyGi',
 'agent', 'active',
 '0345-5555555', 'Liaquatabad, Karachi', '42201-4567890-4', '0333-4443333', 'Sales Executive',
 30000, 1.25, '2026-01-15 10:00:00'),

(6, 'Bilal Mirza', 'bilal@hkbuilders.com',
 '$2y$12$LiSXqjxTBMVzLIoGN3vHdeU3TKqPq7IXRQi7ggS8YRjRXD8nwVyGi',
 'agent', 'active',
 '0333-6666666', 'Gulberg, Karachi', '42301-5678901-5', '0321-3332222', 'Sales Executive',
 28000, 1.00, '2026-02-01 10:00:00'),

-- Agents under Hina (Team B)
(7, 'Fatima Malik', 'fatima@hkbuilders.com',
 '$2y$12$LiSXqjxTBMVzLIoGN3vHdeU3TKqPq7IXRQi7ggS8YRjRXD8nwVyGi',
 'agent', 'active',
 '0300-7777777', 'DHA Phase 5, Karachi', '42401-6789012-6', '0312-2221111', 'Sales Executive',
 30000, 1.25, '2026-02-05 10:00:00'),

(8, 'Kamran Shah', 'kamran@hkbuilders.com',
 '$2y$12$LiSXqjxTBMVzLIoGN3vHdeU3TKqPq7IXRQi7ggS8YRjRXD8nwVyGi',
 'agent', 'active',
 '0321-8888888', 'Korangi, Karachi', '42501-7890123-7', '0300-1110000', 'Junior Sales Executive',
 25000, 1.00, '2026-02-10 10:00:00'),

(9, 'Zainab Qureshi', 'zainab@hkbuilders.com',
 '$2y$12$LiSXqjxTBMVzLIoGN3vHdeU3TKqPq7IXRQi7ggS8YRjRXD8nwVyGi',
 'agent', 'suspended',
 '0312-9999999', 'Saddar, Karachi', '42601-8901234-8', '0345-0009999', 'Sales Executive',
 28000, 1.00, '2026-03-01 10:00:00');

-- ============================================================
-- LEADS (30 realistic leads — various statuses, projects, agents)
-- ============================================================

INSERT INTO leads (name, email, phone, address, project, investment_amount,
                   unit, category, source_id, status_id, priority,
                   initial_notes, assigned_to, claimed_at, created_by, created_at) VALUES

-- Won leads (status 6)
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

-- Negotiation leads (status 5)
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

-- Proposal leads (status 4)
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

-- Qualified leads (status 3)
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

-- Contacted leads (status 2)
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

-- New leads — assigned to agents (status 1)
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

-- Lost leads (status 7)
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

-- Dead leads (status 8)
('Zafar Iqbal', 'zafar.i@yahoo.com', '0345-2020200',
 'Baldia Town, Karachi', NULL, NULL,
 NULL, NULL, 3, 8, 'cold',
 'Phone switched off. Multiple attempts failed.',
 6, '2026-04-20 10:00:00', 1, '2026-04-20 09:00:00'),

-- Unclaimed leads (no agent — in pool)
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

-- A few more assigned ones with different agents
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

-- ============================================================
-- LEAD ACTIVITIES (timeline entries for key leads)
-- ============================================================

INSERT INTO lead_activities (lead_id, user_id, type, note, created_at) VALUES
-- Lead 1 (Ahmed Farooq — Won)
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

-- Lead 2 (Nasreen Bano — Won)
(2, 5, 'claim',        'Lead claimed from pool.',                                    '2026-02-10 11:00:00'),
(2, 5, 'status_change','Status changed to Contacted.',                               '2026-02-11 10:00:00'),
(2, 5, 'note',         'Overseas client. Communication via WhatsApp.',               '2026-02-11 10:10:00'),
(2, 5, 'status_change','Status changed to Qualified.',                               '2026-02-18 09:00:00'),
(2, 5, 'status_change','Status changed to Won.',                                     '2026-03-01 10:00:00'),
(2, 5, 'note',         'Booking confirmed. Full payment overseas transfer.',         '2026-03-01 10:15:00'),

-- Lead 5 (Imran — Negotiation)
(5, 4, 'claim',        'Lead claimed from pool.',                                    '2026-04-01 10:00:00'),
(5, 4, 'status_change','Moved to Contacted.',                                        '2026-04-02 10:00:00'),
(5, 4, 'status_change','Moved to Qualified.',                                        '2026-04-08 10:00:00'),
(5, 4, 'status_change','Moved to Proposal.',                                         '2026-04-14 10:00:00'),
(5, 4, 'status_change','Moved to Negotiation.',                                      '2026-04-20 10:00:00'),
(5, 4, 'note',         'Client wants a 10% discount. Checking with admin.',         '2026-04-20 10:10:00'),

-- Lead 18 (Khalid — Lost)
(18, 7, 'claim',        'Lead claimed.',                                             '2026-03-20 10:00:00'),
(18, 7, 'status_change','Moved to Contacted.',                                       '2026-03-21 10:00:00'),
(18, 7, 'note',         'Budget is 20 lakh, too low for available units.',           '2026-03-21 10:10:00'),
(18, 7, 'status_change','Moved to Lost.',                                            '2026-03-25 10:00:00'),
(18, 7, 'note',         'Could not match budget. Closed as lost.',                  '2026-03-25 10:05:00');

-- ============================================================
-- FOLLOW-UPS
-- ============================================================

INSERT INTO follow_ups (lead_id, agent_id, scheduled_at, note, is_done, done_at, created_at) VALUES
-- Done follow-ups
(5,  4, '2026-04-25 10:00:00', 'Call to discuss discount decision.',         1, '2026-04-25 10:15:00', '2026-04-20 10:30:00'),
(9,  4, '2026-05-05 11:00:00', 'Send payment plan details.',                 1, '2026-05-05 11:10:00', '2026-05-03 09:00:00'),
(13, 7, '2026-05-20 10:00:00', 'Follow up on brochure review.',              1, '2026-05-20 10:20:00', '2026-05-18 11:00:00'),

-- Pending follow-ups
(6,  5, '2026-06-30 10:00:00', 'Call to confirm booking intention.',         0, NULL, '2026-06-25 09:00:00'),
(7,  6, '2026-06-30 11:00:00', 'Share updated payment plan.',                0, NULL, '2026-06-25 10:00:00'),
(10, 5, '2026-07-01 09:00:00', 'Follow up after site visit.',                0, NULL, '2026-06-26 09:00:00'),
(11, 8, '2026-07-02 10:00:00', 'Send commercial unit brochure.',             0, NULL, '2026-06-26 10:00:00'),
(16, 5, '2026-07-03 10:00:00', 'First proper follow-up call.',               0, NULL, '2026-06-27 09:00:00'),
(28, 7, '2026-07-03 11:00:00', 'Schedule site visit.',                       0, NULL, '2026-06-27 10:00:00');

-- ============================================================
-- Done!
-- Login credentials:
--   Admin:           admin@hkbuilders.com    / Admin@1234
--   Sales Manager 1: usman@hkbuilders.com    / Admin@1234
--   Sales Manager 2: hina@hkbuilders.com     / Admin@1234
--   Agent (Ali):     ali@hkbuilders.com      / Admin@1234
--   Agent (Sara):    sara@hkbuilders.com     / Admin@1234
--   Agent (Bilal):   bilal@hkbuilders.com    / Admin@1234
--   Agent (Fatima):  fatima@hkbuilders.com   / Admin@1234
--   Agent (Kamran):  kamran@hkbuilders.com   / Admin@1234
--   Agent (Zainab):  zainab@hkbuilders.com   / Admin@1234  [suspended]
-- ============================================================
