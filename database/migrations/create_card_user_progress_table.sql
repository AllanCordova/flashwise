-- Migration: Create card_user_progress table
-- This table tracks individual user progress for each card
-- Each user has their own spaced repetition data per card

CREATE TABLE IF NOT EXISTS card_user_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT NOT NULL,
    user_id INT NOT NULL,
    ease_factor DECIMAL(3,2) DEFAULT 2.50,
    review_interval INT DEFAULT 0,
    repetitions INT DEFAULT 0,
    next_review DATETIME NULL,
    card_type ENUM('new', 'learning', 'review') DEFAULT 'new',
    last_reviewed TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Each user can only have one progress record per card
    UNIQUE KEY unique_card_user (card_id, user_id),
    
    INDEX idx_card_id (card_id),
    INDEX idx_user_id (user_id),
    INDEX idx_card_type (card_type),
    INDEX idx_next_review (next_review)
);
