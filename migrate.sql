-- =============================================
-- MIGRATION: Add missing columns & tables
-- Safe to run on Aiven (won't delete existing data)
-- =============================================

-- 1. USERS TABLE — add missing columns
ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_pic VARCHAR(255) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS bio TEXT DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_private TINYINT(1) DEFAULT 0;

-- 2. MESSAGES TABLE — add missing column
ALTER TABLE messages ADD COLUMN IF NOT EXISTS attachment VARCHAR(255) DEFAULT NULL;

-- 3. GROUPS TABLE — add missing columns
ALTER TABLE `groups` ADD COLUMN IF NOT EXISTS is_channel TINYINT(1) DEFAULT 0;
ALTER TABLE `groups` ADD COLUMN IF NOT EXISTS channel_type VARCHAR(20) DEFAULT 'public';
ALTER TABLE `groups` ADD COLUMN IF NOT EXISTS description TEXT DEFAULT NULL;
ALTER TABLE `groups` ADD COLUMN IF NOT EXISTS profile_pic VARCHAR(255) DEFAULT NULL;
ALTER TABLE `groups` ADD COLUMN IF NOT EXISTS join_token VARCHAR(64) DEFAULT NULL;
ALTER TABLE `groups` ADD COLUMN IF NOT EXISTS view_only TINYINT(1) DEFAULT 0;

-- 4. GROUP_MEMBERS TABLE — add missing column
ALTER TABLE group_members ADD COLUMN IF NOT EXISTS last_read_id INT DEFAULT 0;

-- 5. GROUP_MESSAGES TABLE — add missing column
ALTER TABLE group_messages ADD COLUMN IF NOT EXISTS attachment VARCHAR(255) DEFAULT NULL;

-- 6. FRIENDS TABLE — create if it doesn't exist
CREATE TABLE IF NOT EXISTS friends (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_friendship (user_id, friend_id)
);
