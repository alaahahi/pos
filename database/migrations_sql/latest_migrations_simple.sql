-- ============================================
-- آخر تعديلات SQL للإغلاقات اليومية والشهرية
-- تاريخ: 2025-12-07
-- ============================================
-- ملاحظة: إذا كانت الأعمدة موجودة بالفعل، سيظهر خطأ يمكن تجاهله
-- ============================================

-- ============================================
-- 1. إضافة حقول الإضافة المباشرة والسحب المباشر
--    إلى جدول daily_closes (الإغلاقات اليومية)
-- ============================================

ALTER TABLE `daily_closes` 
ADD COLUMN `direct_deposits_usd` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `total_sales_iqd`,
ADD COLUMN `direct_deposits_iqd` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `direct_deposits_usd`,
ADD COLUMN `direct_withdrawals_usd` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `direct_deposits_iqd`,
ADD COLUMN `direct_withdrawals_iqd` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `direct_withdrawals_usd`;

-- ============================================
-- 2. إضافة حقول الإضافة المباشرة والسحب المباشر
--    إلى جدول monthly_closes (الإغلاقات الشهرية)
-- ============================================

ALTER TABLE `monthly_closes` 
ADD COLUMN `direct_deposits_usd` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `total_sales_iqd`,
ADD COLUMN `direct_deposits_iqd` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `direct_deposits_usd`,
ADD COLUMN `direct_withdrawals_usd` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `direct_deposits_iqd`,
ADD COLUMN `direct_withdrawals_iqd` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `direct_withdrawals_usd`;

-- ============================================
-- انتهى التعديل
-- ============================================

