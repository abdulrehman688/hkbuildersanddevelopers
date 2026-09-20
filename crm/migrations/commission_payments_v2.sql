-- Commission payments v2 migration
-- Run in phpMyAdmin on hkbuilders database (local and Hostinger)

ALTER TABLE commission_payments
  ADD COLUMN project_id     INT           DEFAULT NULL AFTER lead_id,
  ADD COLUMN sale_date      DATE          DEFAULT NULL AFTER sale_year,
  ADD COLUMN due_date       DATE          DEFAULT NULL AFTER sale_date,
  ADD COLUMN payment_source ENUM('cash','online','cheque','bank_transfer','other') DEFAULT NULL AFTER paid_amount,
  ADD COLUMN reference_no   VARCHAR(100)  DEFAULT NULL AFTER payment_source,
  ADD INDEX  idx_cp_project (project_id);
