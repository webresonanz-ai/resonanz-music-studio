-- ============================================================
-- Migration: Custom seat layouts table
-- Stores JSON layout data for custom seating layouts built
-- in the Custom Layout Builder on the frontend.
-- ============================================================

CREATE TABLE IF NOT EXISTS seat_layouts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    layout_key  VARCHAR(100) NOT NULL UNIQUE
        COMMENT 'Unique key stored in schedules.seat_layout_id, e.g. "custom-1720123456789"',
    name        VARCHAR(150) NOT NULL
        COMMENT 'Display name of the layout, e.g. "Main Hall Custom"',
    venue       VARCHAR(150) NULL
        COMMENT 'Venue name, e.g. "Aula Simfonia Jakarta"',
    description VARCHAR(500) NULL
        COMMENT 'Short description shown in the layout picker',
    total_seats INT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Pre-computed total seat count',
    layout_data LONGTEXT NOT NULL
        COMMENT 'Full layout JSON matching the concertLayouts.js section/row structure',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) COMMENT = 'Custom concert seating layouts saved from the frontend builder';

-- Index for fast lookup by key (used by every seat registration page load)
CREATE INDEX idx_sl_layout_key ON seat_layouts (layout_key);
