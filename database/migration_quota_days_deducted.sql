-- Migration: track exactly how many days a leave request deducted from
-- the employee's quota at approval time (only counts dates that were
-- today-or-future when approved — a leave marked for an already-past
-- date does not reduce quota). Needed so a later edit/delete refunds the
-- correct amount, since "today" keeps moving and can't be recomputed
-- after the fact.

USE `absensi_sekolah`;

ALTER TABLE `leave_requests`
  ADD COLUMN `quota_days_deducted` INT UNSIGNED NOT NULL DEFAULT 0;
