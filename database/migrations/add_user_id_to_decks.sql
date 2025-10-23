-- Migration: Add user_id to decks table
-- This migration adds user_id column to existing decks table
-- and associates all existing decks with the first user (admin)

-- Add user_id column (allow NULL temporarily)
ALTER TABLE decks 
ADD COLUMN user_id INT NULL AFTER category_id,
ADD INDEX idx_user_id (user_id);

-- Set all existing decks to belong to the first user
-- You may need to adjust this based on your data
UPDATE decks 
SET user_id = (SELECT id FROM users ORDER BY id ASC LIMIT 1)
WHERE user_id IS NULL;

-- Make user_id NOT NULL and add foreign key
ALTER TABLE decks 
MODIFY COLUMN user_id INT NOT NULL,
ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
