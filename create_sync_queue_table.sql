-- SQL Statement لإنشاء جدول sync_queue على السيرفر (MySQL)
-- استخدم هذا الـ SQL مباشرة على قاعدة البيانات

CREATE TABLE IF NOT EXISTS `sync_queue` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `table_name` VARCHAR(255) NOT NULL,
  `record_id` BIGINT UNSIGNED NOT NULL,
  `action` ENUM('insert', 'update', 'delete') NOT NULL,
  `data` JSON NULL COMMENT 'البيانات الكاملة للسجل',
  `changes` JSON NULL COMMENT 'الحقول التي تغيرت (للتحديثات)',
  `status` ENUM('pending', 'synced', 'failed') NOT NULL DEFAULT 'pending',
  `retry_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `error_message` TEXT NULL,
  `synced_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_table_record_status` (`table_name`, `record_id`, `status`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

