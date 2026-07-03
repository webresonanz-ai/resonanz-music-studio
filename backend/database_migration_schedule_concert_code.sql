-- Migration: Add concert_code column to schedules table
-- Used as the QR code prefix for concert audience tickets.

ALTER TABLE schedules
    ADD COLUMN concert_code VARCHAR(50) NULL DEFAULT NULL
        COMMENT 'Optional concert code used as QR code prefix for audience tickets'
        AFTER venue;
