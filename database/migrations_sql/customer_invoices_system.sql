-- SQL modifications for Customer Invoices System
-- This file contains SQL statements to ensure the database structure supports the customer invoices feature

-- Check if orders table has all required columns
-- If not, add them:

-- 1. Ensure total_paid column exists (should already exist from migration 2025_09_28_044911)
-- If it doesn't exist, uncomment the following:
-- ALTER TABLE orders ADD COLUMN IF NOT EXISTS total_paid DECIMAL(10, 2) DEFAULT 0 AFTER total_amount;

-- 2. Ensure date column exists (should already exist from migration 2025_09_28_044911)
-- If it doesn't exist, uncomment the following:
-- ALTER TABLE orders ADD COLUMN IF NOT EXISTS date DATE NULL AFTER notes;

-- 3. Ensure final_amount column exists (should already exist from migration 2025_09_26_220711)
-- If it doesn't exist, uncomment the following:
-- ALTER TABLE orders ADD COLUMN IF NOT EXISTS final_amount DECIMAL(10, 2) DEFAULT 0 AFTER discount_amount;

-- 4. Ensure discount_amount column exists (should already exist from migration 2025_09_28_084911)
-- If it doesn't exist, uncomment the following:
-- ALTER TABLE orders ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10, 2) DEFAULT 0 AFTER total_amount;

-- 5. Update existing orders to calculate final_amount if it's 0
-- This ensures backward compatibility with old orders
UPDATE orders 
SET final_amount = total_amount - COALESCE(discount_amount, 0)
WHERE final_amount = 0 OR final_amount IS NULL;

-- 6. Update existing orders to set status based on payment
-- If total_paid >= final_amount, set status to 'paid', otherwise 'due'
UPDATE orders 
SET status = CASE 
    WHEN COALESCE(total_paid, 0) >= (total_amount - COALESCE(discount_amount, 0)) THEN 'paid'
    ELSE 'due'
END
WHERE status NOT IN ('paid', 'due', 'pending', 'completed', 'cancelled')
   OR (status = 'completed' AND COALESCE(total_paid, 0) < (total_amount - COALESCE(discount_amount, 0)));

-- 7. Ensure customer_balances table exists (should already exist)
-- If it doesn't exist, uncomment the following:
/*
CREATE TABLE IF NOT EXISTS customer_balances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    balance_dollar DECIMAL(10, 2) DEFAULT 0,
    balance_dinar DECIMAL(10, 2) DEFAULT 0,
    currency_preference ENUM('dollar', 'dinar') DEFAULT 'dinar',
    last_transaction_date DATETIME NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_customer_balance (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
*/

-- 8. Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_orders_customer_status ON orders(customer_id, status);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);
CREATE INDEX IF NOT EXISTS idx_orders_date ON orders(date);

-- Notes:
-- - All columns should already exist from previous migrations
-- - This SQL file is for reference and manual fixes if needed
-- - Run these statements only if you encounter missing columns
-- - Always backup your database before running any ALTER TABLE statements

