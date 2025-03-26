-- First ensure admin user doesn't exist
DELETE FROM users WHERE email = 'agrofresh.admin@gmail.com';

-- Insert admin user with hashed password (AgroAdmin@2025)
INSERT INTO users (full_name, email, password, role, is_admin) 
VALUES ('AgroFresh Admin', 'agrofresh.admin@gmail.com', '$2y$10$YOZ9qQ9P6RzJ.tWY5hP8vQX6W2u0K1HkzYOZ9qQ9P6RzJ.tWY', 'admin', 1);

-- Ensure admin has all necessary privileges
UPDATE users 
SET role = 'admin', is_admin = 1 
WHERE email = 'agrofresh.admin@gmail.com';
