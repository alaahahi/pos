-- SQL modifications for Active Users System
-- This file contains SQL statements to ensure the database structure supports the active users feature

-- 1. Add last_activity_at column to users table
-- If it doesn't exist, uncomment the following:
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_activity_at TIMESTAMP NULL AFTER updated_at;

-- 2. Create index for better performance when querying active users
CREATE INDEX IF NOT EXISTS idx_users_last_activity_at ON users(last_activity_at);
CREATE INDEX IF NOT EXISTS idx_users_is_active_last_activity ON users(is_active, last_activity_at);

-- 3. Update existing users to set last_activity_at to updated_at if null
-- This ensures backward compatibility
UPDATE users 
SET last_activity_at = updated_at 
WHERE last_activity_at IS NULL AND updated_at IS NOT NULL;

-- Notes:
-- - The migration file should handle the column creation automatically
-- - This SQL file is for reference and manual fixes if needed
-- - Always backup your database before running any ALTER TABLE statements
-- - The system considers users active if their last_activity_at is within the last 3 minutes

