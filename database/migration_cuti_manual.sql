-- Migration: support "Input Cuti Manual" feature (mendadak / force majeure)
-- Adds a new leave type 'darurat' to the existing enum.
-- Safe to run on the live DB — MODIFY only widens the enum, no data is touched.

USE `absensi_sekolah`;

ALTER TABLE `leave_requests`
  MODIFY `jenis` enum('sakit','tahunan','melahirkan','menikah','darurat') NOT NULL;
