-- ============================================
-- جميع تعديلات SQL للنظام
-- تاريخ: 2025-12-07
-- ============================================
-- ملاحظة: إذا كانت الجداول أو الأعمدة موجودة بالفعل، سيظهر خطأ يمكن تجاهله
-- ============================================

-- ============================================
-- الجزء الأول: تعديلات الإغلاقات اليومية والشهرية
-- ============================================

-- 1. إضافة حقول الإضافة المباشرة والسحب المباشر
--    إلى جدول daily_closes (الإغلاقات اليومية)
ALTER TABLE `daily_closes` 
ADD COLUMN `direct_deposits_usd` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `total_sales_iqd`,
ADD COLUMN `direct_deposits_iqd` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `direct_deposits_usd`,
ADD COLUMN `direct_withdrawals_usd` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `direct_deposits_iqd`,
ADD COLUMN `direct_withdrawals_iqd` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `direct_withdrawals_usd`;

-- 2. إضافة حقول الإضافة المباشرة والسحب المباشر
--    إلى جدول monthly_closes (الإغلاقات الشهرية)
ALTER TABLE `monthly_closes` 
ADD COLUMN `direct_deposits_usd` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `total_sales_iqd`,
ADD COLUMN `direct_deposits_iqd` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `direct_deposits_usd`,
ADD COLUMN `direct_withdrawals_usd` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `direct_deposits_iqd`,
ADD COLUMN `direct_withdrawals_iqd` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `direct_withdrawals_usd`;

-- ============================================
-- الجزء الثاني: تعديلات التصنيفات
-- ============================================

-- 1. إنشاء جدول categories (التصنيفات)
CREATE TABLE IF NOT EXISTS `categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NULL DEFAULT NULL,
  `description` TEXT NULL DEFAULT NULL,
  `color` VARCHAR(7) NOT NULL DEFAULT '#667eea',
  `icon` VARCHAR(255) NULL DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. إضافة عمود category_id إلى جدول products
ALTER TABLE `products` 
ADD COLUMN `category_id` BIGINT UNSIGNED NULL AFTER `id`,
ADD CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

-- 3. إضافة فهرس لتحسين الأداء
CREATE INDEX `products_category_id_index` ON `products` (`category_id`);

-- ============================================
-- انتهى جميع التعديلات
-- ============================================

