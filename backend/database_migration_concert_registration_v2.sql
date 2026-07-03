-- ============================================================
-- Migration: Concert registration improvements
-- 1. Add is_open_register + audience_capacity to schedules
-- 2. Add schedule_id to concert_audiences
-- ============================================================

-- 1. New columns on schedules
ALTER TABLE schedules
    ADD COLUMN is_open_register  TINYINT(1)   NOT NULL DEFAULT 0
        COMMENT '1 = registration is open for this concert schedule'
        AFTER description,
    ADD COLUMN audience_capacity INT          NULL DEFAULT NULL
        COMMENT 'Max audience registrations for this concert; NULL = unlimited'
        AFTER is_open_register;

-- 2. New nullable FK on concert_audiences pointing back to the concert schedule
ALTER TABLE concert_audiences
    ADD COLUMN schedule_id INT NULL DEFAULT NULL
        COMMENT 'FK to schedules.id (type=concert) this registration belongs to'
        AFTER program_id,
    ADD CONSTRAINT fk_ca_schedule
        FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE SET NULL;

-- Index to make per-concert count queries fast
CREATE INDEX idx_ca_schedule_id ON concert_audiences (schedule_id);
