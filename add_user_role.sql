-- Add role column to users table
ALTER TABLE users
ADD COLUMN role ENUM('user', 'admin') NOT NULL DEFAULT 'user';

-- Update existing admin users if needed
-- You can run this after identifying admin users
-- UPDATE users SET role = 'admin' WHERE id IN (your_admin_ids);
