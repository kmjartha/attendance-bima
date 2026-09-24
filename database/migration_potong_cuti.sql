-- Migration: fitur "Potong Cuti" (HRD mengurangi jatah cuti karyawan).
--
-- Setiap pemotongan dicatat sebagai satu baris di tabel ini: siapa yang
-- dipotong, siapa HRD yang memotong, berapa hari, alasan, dan sisa cuti
-- sebelum/sesudah. Baris inilah yang dipakai sebagai sumber notifikasi ke
-- karyawan yang bersangkutan (type notifikasi: 'leave_deduction').
--
-- Aman dijalankan lebih dari sekali (CREATE TABLE IF NOT EXISTS).
-- Tidak memakai "USE <db>" — pilih dulu database aplikasi di phpMyAdmin
-- sebelum Import, supaya jalan di database lokal maupun produksi.

CREATE TABLE IF NOT EXISTS `leave_deductions` (
  `id`           int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      int(10) UNSIGNED NOT NULL,
  `deducted_by`  int(10) UNSIGNED DEFAULT NULL,
  `jumlah_hari`  int(10) UNSIGNED NOT NULL,
  `sisa_sebelum` int(11) NOT NULL,
  `sisa_sesudah` int(11) NOT NULL,
  `alasan`       varchar(1000) NOT NULL,
  `created_at`   timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ld_user_created` (`user_id`,`created_at`),
  KEY `idx_ld_deducted_by` (`deducted_by`),
  CONSTRAINT `fk_ld_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ld_hr` FOREIGN KEY (`deducted_by`)
    REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
