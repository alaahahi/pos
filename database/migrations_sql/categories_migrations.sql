-- ============================================
-- تعديلات SQL للتصنيفات (Categories)
-- تاريخ: 2025-12-07
-- ============================================
-- ملاحظة: إذا كانت الجداول أو الأعمدة موجودة بالفعل، سيظهر خطأ يمكن تجاهله
-- ============================================

-- ============================================
-- 1. إنشاء جدول categories (التصنيفات)
-- ============================================

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

-- ============================================
-- 2. إضافة عمود category_id إلى جدول products
-- ============================================

-- التحقق من وجود العمود قبل الإضافة
SET @dbname = DATABASE();
SET @tablename = "products";
SET @columnname = "category_id";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column category_id already exists in products table.';",
  "ALTER TABLE `products` ADD COLUMN `category_id` BIGINT UNSIGNED NULL AFTER `id`, ADD CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ============================================
-- 3. إضافة فهرس لتحسين الأداء
-- ============================================

-- فهرس على category_id في جدول products
SET @indexname = "products_category_id_index";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (table_name = 'products')
      AND (table_schema = @dbname)
      AND (index_name = @indexname)
  ) > 0,
  "SELECT 'Index products_category_id_index already exists.';",
  "CREATE INDEX `products_category_id_index` ON `products` (`category_id`);"
));
PREPARE createIndexIfNotExists FROM @preparedStatement;
EXECUTE createIndexIfNotExists;
DEALLOCATE PREPARE createIndexIfNotExists;

-- ============================================
-- انتهى التعديل
-- ============================================

