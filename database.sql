-- ==========================================================
-- Database Schema: Sistem Penilaian Kelulusan Dewan Ambalan
-- ==========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------
-- Table structure for users
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nama_lengkap` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','peserta') NOT NULL DEFAULT 'peserta',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nama_lengkap` (`nama_lengkap`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Table structure for penilaian
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `penilaian` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nama_lengkap` VARCHAR(255) NOT NULL,
  `nilai_wawancara` DOUBLE NOT NULL DEFAULT 0,
  `nilai_tes_tulis` DOUBLE NOT NULL DEFAULT 0,
  `nilai_cv_proker` DOUBLE NOT NULL DEFAULT 0,
  `tanggal` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_penilaian_nama` (`nama_lengkap`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Table structure for app_settings
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `app_settings` (
  `setting_key` VARCHAR(100) NOT NULL PRIMARY KEY,
  `setting_value` VARCHAR(255) NOT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Default Settings (KKM Default: 70)
-- ----------------------------------------------------------
INSERT INTO `app_settings` (`setting_key`, `setting_value`) 
VALUES ('kkm', '70.00')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- ----------------------------------------------------------
-- Sample Admin Account (Password: Dewan Ambalan 2025)
-- Harap ubah password setelah instalasi pertama!
-- ----------------------------------------------------------
INSERT INTO `users` (`nama_lengkap`, `password`, `role`) 
VALUES ('Admin DA', 'Dewan Ambalan 2025', 'admin')
ON DUPLICATE KEY UPDATE `role` = VALUES(`role`);

SET FOREIGN_KEY_CHECKS = 1;
