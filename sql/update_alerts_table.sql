-- Add message column if it doesn't exist
ALTER TABLE alerts 
ADD COLUMN IF NOT EXISTS message TEXT AFTER last_value;

-- Add created_at column if it doesn't exist
ALTER TABLE alerts 
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER message;

-- Add last_triggered column if it doesn't exist
ALTER TABLE alerts 
ADD COLUMN IF NOT EXISTS last_triggered TIMESTAMP NULL AFTER created_at;

-- Add last_value column if it doesn't exist
ALTER TABLE alerts 
ADD COLUMN IF NOT EXISTS last_value DECIMAL(10,2) NULL AFTER threshold; 