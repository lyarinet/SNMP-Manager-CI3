-- Create database
CREATE DATABASE IF NOT EXISTS snmp_manager;

-- Create user with password
CREATE USER IF NOT EXISTS 'snmp_user'@'localhost' IDENTIFIED BY 'snmp_password123';

-- Grant privileges to the user
GRANT ALL PRIVILEGES ON snmp_manager.* TO 'snmp_user'@'localhost';

-- Apply privileges
FLUSH PRIVILEGES;

-- Switch to the database
USE snmp_manager;

-- Create tables (from schema.sql)
-- ... (copy tables from schema.sql)

-- Create default admin user
INSERT INTO users (username, password, email, role) 
VALUES (
    'admin', 
    '$2y$10$8tdsR.ACj.6YEWt5QkDSAOT.lzGOWIkJ.zXLsL7vCt6oHwzDxT.m6',  -- Hashed password for 'admin123'
    'admin@snmpmanager.local',
    'admin'
); 