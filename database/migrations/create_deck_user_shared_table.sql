-- Migration: Create deck_user_shared table for N:N relationship
-- This table represents the many-to-many relationship between decks and users
-- When a deck is shared with a user, a record is created in this table

CREATE TABLE IF NOT EXISTS deck_user_shared (
    id INT AUTO_INCREMENT PRIMARY KEY,
    deck_id INT NOT NULL,
    user_id INT NOT NULL,
    shared_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (deck_id) REFERENCES decks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Ensure a deck can only be shared once with the same user
    UNIQUE KEY unique_deck_user (deck_id, user_id),
    
    INDEX idx_deck_id (deck_id),
    INDEX idx_user_id (user_id)
);
