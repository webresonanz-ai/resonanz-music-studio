-- ============================================================
-- Migration: Add is_seat_assign and seat layout to schedules,
--            and seat_number to concert_audiences
-- ============================================================

-- 1. Add is_seat_assign flag to schedules
ALTER TABLE schedules
    ADD COLUMN is_seat_assign TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 = this concert uses seat selection; guests pick a seat on registration'
        AFTER audience_capacity,
    ADD COLUMN seat_rows INT UNSIGNED NULL DEFAULT NULL
        COMMENT 'Number of rows in the seating layout (e.g. 10)'
        AFTER is_seat_assign,
    ADD COLUMN seat_columns INT UNSIGNED NULL DEFAULT NULL
        COMMENT 'Number of columns/seats per row in the seating layout (e.g. 20)'
        AFTER seat_rows;

-- 2. Add seat_number to concert_audiences
ALTER TABLE concert_audiences
    ADD COLUMN seat_number VARCHAR(20) NULL DEFAULT NULL
        COMMENT 'Chosen seat label, e.g. "A5" or "C12". NULL for non-seated concerts.'
        AFTER ticket_quantity;

-- Index for fast seat availability lookup
CREATE INDEX idx_ca_schedule_seat ON concert_audiences (schedule_id, seat_number);
