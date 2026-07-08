-- ============================================================
-- Migration: Seat holds table
-- Temporarily locks a seat when a logged-in user clicks it,
-- preventing race conditions during the checkout flow.
-- Hold expires automatically after 10 minutes.
-- ============================================================

CREATE TABLE IF NOT EXISTS seat_holds (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL
        COMMENT 'FK to schedules.id',
    seat_number VARCHAR(20) NOT NULL
        COMMENT 'Seat label, e.g. "A5" or "C12"',
    user_id     INT NOT NULL
        COMMENT 'FK to users.id — the user holding this seat',
    held_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        COMMENT 'When the hold was created',
    expires_at  TIMESTAMP NOT NULL
        COMMENT 'Hold expires at this time (held_at + 10 min)',

    UNIQUE KEY uk_schedule_seat (schedule_id, seat_number),
    INDEX idx_sh_user    (user_id),
    INDEX idx_sh_expires (expires_at),

    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE
) COMMENT = 'Temporary seat reservations during checkout (TTL: 10 minutes)';
