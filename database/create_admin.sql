-- First, clear any existing users (optional, be careful in production)
TRUNCATE TABLE users;

-- Create default admin user
-- Default credentials:
-- Username: admin
-- Password: admin123
-- Email: admin@snmpmanager.local

INSERT INTO users (username, password, email, role) 
VALUES (
    'admin', 
    '$2y$10$YEMQXXQQvPrR.L3CO8oreOqxTfvYtxgEh0xyVEduGrEBHyIZyA8Hy',  -- Hashed password for 'admin123'
    'admin@snmpmanager.local',
    'admin'
); 