-- Migration v3: Add detail column to audit_log
-- Run this on both local and Hostinger via phpMyAdmin

ALTER TABLE audit_log ADD COLUMN detail VARCHAR(500) NULL AFTER target_id;
