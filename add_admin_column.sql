-- Add is_admin column to users table
ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0;

-- Set admin flag for admin user
UPDATE users SET is_admin = 1 WHERE email = 'admin@agrofresh.com';
