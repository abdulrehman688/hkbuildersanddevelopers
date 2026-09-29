-- ============================================================
-- Units v2: payments linked to units, unit maturity status,
-- and the foreign keys the first units migration could not add.
-- Run once in phpMyAdmin on the CRM database, after builder_units.sql.
-- Export a backup of the database before running it.
-- ============================================================

-- 1. Remove units whose builder or project no longer exists.
DELETE FROM builder_units
WHERE builder_id NOT IN (SELECT id FROM builders)
   OR project_id NOT IN (SELECT id FROM builder_projects);

-- 2. Match the parent id types (INT UNSIGNED) so the foreign keys can be created,
--    and add the maturity status.
ALTER TABLE builder_units
  MODIFY id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  MODIFY builder_id INT UNSIGNED NOT NULL,
  MODIFY project_id INT UNSIGNED NOT NULL,
  ADD COLUMN maturity_status ENUM('immature','mature') NOT NULL DEFAULT 'immature' AFTER commission_amount,
  ADD CONSTRAINT fk_units_builder FOREIGN KEY (builder_id) REFERENCES builders(id)         ON DELETE CASCADE,
  ADD CONSTRAINT fk_units_project FOREIGN KEY (project_id) REFERENCES builder_projects(id) ON DELETE CASCADE;

-- 3. A payment can now be linked to the unit it pays for.
--    Deleting a unit keeps its payments, they just become unassigned.
ALTER TABLE builder_payments
  ADD COLUMN unit_id INT UNSIGNED NULL AFTER project_id,
  ADD CONSTRAINT fk_payments_unit FOREIGN KEY (unit_id) REFERENCES builder_units(id) ON DELETE SET NULL;

-- 4. Units that were marked paid by hand become real payment records.
INSERT INTO builder_payments
    (builder_id, project_id, unit_id, amount, payment_type, payment_date,
     payment_month, payment_year, notes, created_by)
SELECT builder_id, project_id, id, commission_amount, 'final', DATE(created_at),
       MONTH(created_at), YEAR(created_at),
       'Recorded automatically: unit was marked paid before payments were linked to units',
       COALESCE(created_by, 0)
FROM builder_units
WHERE commission_status = 'paid' AND commission_amount > 0;

-- 5. Paid / unpaid is now calculated from payments, so the manual column is removed.
ALTER TABLE builder_units DROP COLUMN commission_status;
