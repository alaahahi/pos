-- ============================================
-- آخر تعديلات SQL للإغلاقات اليومية والشهرية
-- تاريخ: 2025-12-07
-- ============================================

-- ============================================
-- 1. إضافة حقول الإضافة المباشرة والسحب المباشر
--    إلى جدول daily_closes (الإغلاقات اليومية)
-- ============================================

-- التحقق من وجود الأعمدة قبل الإضافة
SET @dbname = DATABASE();
SET @tablename = "daily_closes";
SET @columnname = "direct_deposits_usd";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column direct_deposits_usd already exists in daily_closes.';",
  "ALTER TABLE `daily_closes` ADD COLUMN `direct_deposits_usd` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `total_sales_iqd`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = "direct_deposits_iqd";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column direct_deposits_iqd already exists in daily_closes.';",
  "ALTER TABLE `daily_closes` ADD COLUMN `direct_deposits_iqd` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `direct_deposits_usd`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = "direct_withdrawals_usd";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column direct_withdrawals_usd already exists in daily_closes.';",
  "ALTER TABLE `daily_closes` ADD COLUMN `direct_withdrawals_usd` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `direct_deposits_iqd`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = "direct_withdrawals_iqd";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column direct_withdrawals_iqd already exists in daily_closes.';",
  "ALTER TABLE `daily_closes` ADD COLUMN `direct_withdrawals_iqd` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `direct_withdrawals_usd`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ============================================
-- 2. إضافة حقول الإضافة المباشرة والسحب المباشر
--    إلى جدول monthly_closes (الإغلاقات الشهرية)
-- ============================================

SET @tablename = "monthly_closes";
SET @columnname = "direct_deposits_usd";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column direct_deposits_usd already exists in monthly_closes.';",
  "ALTER TABLE `monthly_closes` ADD COLUMN `direct_deposits_usd` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `total_sales_iqd`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = "direct_deposits_iqd";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column direct_deposits_iqd already exists in monthly_closes.';",
  "ALTER TABLE `monthly_closes` ADD COLUMN `direct_deposits_iqd` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `direct_deposits_usd`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = "direct_withdrawals_usd";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column direct_withdrawals_usd already exists in monthly_closes.';",
  "ALTER TABLE `monthly_closes` ADD COLUMN `direct_withdrawals_usd` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `direct_deposits_iqd`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = "direct_withdrawals_iqd";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column direct_withdrawals_iqd already exists in monthly_closes.';",
  "ALTER TABLE `monthly_closes` ADD COLUMN `direct_withdrawals_iqd` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `direct_withdrawals_usd`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ============================================
-- انتهى التعديل
-- ============================================

