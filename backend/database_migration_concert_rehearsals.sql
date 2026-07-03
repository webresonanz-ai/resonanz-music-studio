-- ============================================================
-- Migration: BMS Concert Rehearsal Links
-- Explicitly links practice schedules to a BMS concert schedule.
-- Run this on existing installations.
-- ============================================================

-- 1. Add singers_manager role to users table
ALTER TABLE users
    MODIFY COLUMN role ENUM('admin', 'manager', 'singers_manager', 'teacher', 'arranger', 'member') DEFAULT 'member';

-- 2. Create concert_rehearsals pivot table
CREATE TABLE IF NOT EXISTS concert_rehearsals (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    concert_schedule_id INT NOT NULL COMMENT 'FK to schedules.id where type=concert',
    rehearsal_id        INT NOT NULL COMMENT 'FK to schedules.id where type=practice',
    sort_order          TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_concert_rehearsal (concert_schedule_id, rehearsal_id),
    FOREIGN KEY (concert_schedule_id) REFERENCES schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (rehearsal_id)        REFERENCES schedules(id) ON DELETE CASCADE
);
