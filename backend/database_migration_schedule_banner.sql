-- ============================================================
-- Migration: Add banner_url column to schedules table
-- Used to store a concert banner image URL for homepage slideshow.
-- Run this on existing installations.
-- ============================================================

ALTER TABLE schedules
    ADD COLUMN banner_url VARCHAR(500) NULL DEFAULT NULL
        COMMENT 'Optional banner image URL, shown on homepage slideshow for concert type schedules'
    AFTER description;
