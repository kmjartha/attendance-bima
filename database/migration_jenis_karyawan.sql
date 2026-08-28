-- Migration: add the employee type used by the employee create/edit forms.

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `jenis_karyawan`
    ENUM('kontrak','kontrak_yayasan') DEFAULT NULL
    AFTER `face_descriptor`;