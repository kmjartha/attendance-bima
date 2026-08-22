-- Migration: one leave_requests ROW per submission, regardless of how many
-- (possibly non-contiguous) dates it covers. Individual dates — each with
-- its own optional note — live in this child table. tanggal_mulai/
-- tanggal_selesai on leave_requests are kept as MIN/MAX of the child dates,
-- for backward-compatible range display only; the child table is now the
-- source of truth for exactly which dates are covered.

USE `absensi_sekolah`;

CREATE TABLE `leave_request_dates` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `leave_request_id` int(10) UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_leave_date` (`leave_request_id`,`tanggal`),
  KEY `idx_leave_request` (`leave_request_id`),
  CONSTRAINT `fk_lrd_leave` FOREIGN KEY (`leave_request_id`)
    REFERENCES `leave_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
