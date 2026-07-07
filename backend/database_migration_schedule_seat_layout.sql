-- ============================================================
-- Migration: Replace seat_rows / seat_columns with seat_layout_id
-- seat_layout_id stores a predefined layout key (e.g. "aula-simfonia-standard")
-- defined in the frontend ConcertLayouts.js registry.
-- ============================================================

-- Add new column (safe to run even if seat_rows/seat_columns already exist)
ALTER TABLE schedules
    ADD COLUMN seat_layout_id VARCHAR(80) NULL DEFAULT NULL
        COMMENT 'ID of the predefined seating layout from ConcertLayouts.js'
        AFTER is_seat_assign;

-- Optionally drop the old columns if they were added by the previous migration
-- (comment out if you want to keep them for now)
-- ALTER TABLE schedules DROP COLUMN seat_rows, DROP COLUMN seat_columns;
