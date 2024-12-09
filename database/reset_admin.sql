-- First, clear existing users
TRUNCATE TABLE users;

-- Create admin user with password: admin123
INSERT INTO users (username, password, email, role) 
VALUES (
    'admin',
    -- This is the hash for 'admin123'
    '$2y$10$yJ5HQPWlBBFZsO4TkR.PYOQNlwx8HaZJ0zW.jkVJM1yqSs1YJ8Qr2',
    'admin@snmpmanager.local',
    'admin'
); 