-- Migration: Add venue column to schedules table
-- Used to store a location/venue for schedules.

ALTER TABLE schedules
    ADD COLUMN venue VARCHAR(150) NULL DEFAULT NULL
        COMMENT 'Optional schedule venue/location'
        AFTER end_time;
