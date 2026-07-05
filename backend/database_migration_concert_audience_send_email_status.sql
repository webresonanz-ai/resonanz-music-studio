-- ============================================================
-- Migration: Track ticket email delivery status
-- Adds send_email_status to concert_audiences.
-- Values:
--   pending = email has not been attempted yet
--   sent    = PHPMailer reported successful send
--   failed  = PHPMailer reported failed send
-- ============================================================

ALTER TABLE concert_audiences
    ADD COLUMN send_email_status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending'
        AFTER attended_at;
