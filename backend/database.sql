-- Database: resonanz_music_studio

-- Programs table
CREATE TABLE programs (
    id VARCHAR(10) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Users table for authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    api_token VARCHAR(64) UNIQUE,
    api_token_exp TIMESTAMP NULL,
    role ENUM('admin', 'manager', 'teacher', 'arranger', 'member') DEFAULT 'member',
    program_id VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id)
);

-- Teachers table
CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id VARCHAR(10) NOT NULL,
    name VARCHAR(100) NOT NULL,
    position VARCHAR(100),
    bio TEXT,
    photo VARCHAR(255),
    FOREIGN KEY (program_id) REFERENCES programs(id)
);

-- Courses table
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id VARCHAR(10) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    fee DECIMAL(10, 2),
    duration VARCHAR(50),
    FOREIGN KEY (program_id) REFERENCES programs(id)
);

-- Events table
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id VARCHAR(10) NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    event_date DATETIME NOT NULL,
    location VARCHAR(150),
    FOREIGN KEY (program_id) REFERENCES programs(id)
);

-- Schedules table
CREATE TABLE schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id VARCHAR(10) NOT NULL,
    title VARCHAR(150) NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'lesson',
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id)
);

-- Schedule programs pivot table for multi-program collaboration
CREATE TABLE schedule_programs (
    schedule_id INT NOT NULL,
    program_id VARCHAR(10) NOT NULL,
    PRIMARY KEY (schedule_id, program_id),
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE
);

-- Members table
CREATE TABLE members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id VARCHAR(10) NOT NULL,
    user_id INT,
    name VARCHAR(100) NOT NULL,
    instrument VARCHAR(100),
    join_date DATE,
    status ENUM('active', 'inactive', 'alumni') DEFAULT 'active',
    FOREIGN KEY (program_id) REFERENCES programs(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Attendance table (legacy — tied to events)
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    member_id INT NOT NULL,
    status ENUM('present', 'absent', 'late') DEFAULT 'present',
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

-- Concert roster — singers assigned to a BMS concert schedule
CREATE TABLE concert_roster (
    id INT AUTO_INCREMENT PRIMARY KEY,
    concert_schedule_id INT NOT NULL,
    member_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_concert_member (concert_schedule_id, member_id),
    FOREIGN KEY (concert_schedule_id) REFERENCES schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

-- Schedule attendance — rehearsal attendance per singer
CREATE TABLE schedule_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    member_id INT NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') NOT NULL DEFAULT 'present',
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_schedule_member (schedule_id, member_id),
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

-- News table
CREATE TABLE news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id VARCHAR(10),
    title VARCHAR(150) NOT NULL,
    content TEXT,
    published_at DATE,
    FOREIGN KEY (program_id) REFERENCES programs(id)
);

-- Concerts table
CREATE TABLE concerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id VARCHAR(10),
    title VARCHAR(150) NOT NULL,
    description TEXT,
    concert_date DATETIME NOT NULL,
    venue VARCHAR(150),
    FOREIGN KEY (program_id) REFERENCES programs(id)
);

-- Concert audiences table
CREATE TABLE concert_audiences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id VARCHAR(10) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    concert_title VARCHAR(150) NOT NULL,
    ticket_quantity INT NOT NULL DEFAULT 1,
    notes TEXT,
    qr_code VARCHAR(100) DEFAULT NULL COMMENT 'Format: {firstWord}_{id}_{timestamp}_{rand4}',
    attended_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Set when the ticket QR is scanned at the door',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id)
);

-- Migration: add qr_code to existing installations
-- ALTER TABLE concert_audiences ADD COLUMN qr_code VARCHAR(100) DEFAULT NULL COMMENT 'Format: {firstWord}_{id}_{timestamp}_{rand4}' AFTER notes;

-- Migration: add attended_at for ticket scan check-in
-- ALTER TABLE concert_audiences ADD COLUMN attended_at TIMESTAMP NULL DEFAULT NULL AFTER qr_code;

-- Gallery table
CREATE TABLE gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id VARCHAR(10),
    title VARCHAR(150),
    image_url VARCHAR(255) NOT NULL,
    caption TEXT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id)
);

-- Achievements table
CREATE TABLE achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id VARCHAR(10) NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    year YEAR(4),
    FOREIGN KEY (program_id) REFERENCES programs(id)
);

-- Testimonials table
CREATE TABLE testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id VARCHAR(10) NOT NULL,
    author VARCHAR(100) NOT NULL,
    content TEXT,
    FOREIGN KEY (program_id) REFERENCES programs(id)
);

-- Contact messages table
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id VARCHAR(10),
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(150),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id)
);

-- Insert initial programs
INSERT INTO programs (id, name, description, icon) VALUES
('trms', 'TRMS', 'The Resonanz Music Studio', 'bi-music-note-beamed'),
('bms', 'BMS', 'Batavia Madrigal Singers', 'bi-people-fill'),
('jco', 'JCO', 'Jakarta Concert Orchestra', 'bi-vinyl-fill'),
('trcc', 'TRCC', 'The Resonanz Children Choir', 'bi-trophy-fill');

-- ============================================================
-- Migration: Expand members table for BMS full member profile
-- Run this if you already have the members table from the
-- initial schema above. If creating fresh, use the new
-- CREATE TABLE below instead.
-- ============================================================

-- Option A: ALTER existing table
ALTER TABLE members
    ADD COLUMN nickname      VARCHAR(100)  NULL                                                         AFTER name,
    ADD COLUMN email         VARCHAR(150)  NULL                                                         AFTER nickname,
    ADD COLUMN stage_name    VARCHAR(100)  NULL                                                         AFTER email,
    ADD COLUMN birth_place   VARCHAR(100)  NULL                                                         AFTER stage_name,
    ADD COLUMN birth_date    DATE          NULL                                                         AFTER birth_place,
    ADD COLUMN domicile      VARCHAR(150)  NULL                                                         AFTER birth_date,
    ADD COLUMN phone         VARCHAR(30)   NULL                                                         AFTER domicile,
    ADD COLUMN year_join     VARCHAR(10)   NULL                                                         AFTER phone,
    ADD COLUMN field_of_work VARCHAR(100)  NULL                                                         AFTER year_join,
    ADD COLUMN section       VARCHAR(100)  NULL                                                         AFTER instrument,
    ADD COLUMN performances  INT           NOT NULL DEFAULT 0                                           AFTER section,
    ADD COLUMN avatar        VARCHAR(255)  NOT NULL DEFAULT 'https://voca-land.sgp1.cdn.digitaloceanspaces.com/0/1757684222527/9465e2e8.jpg',
    MODIFY COLUMN role       ENUM('Sopran','Alto','Tenor','Bass') NULL,
    MODIFY COLUMN status     ENUM('active','passive') NOT NULL DEFAULT 'active',
    ADD COLUMN updated_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Option B: Full CREATE for a fresh install (replaces the earlier members table definition)
DROP TABLE IF EXISTS members;
CREATE TABLE members (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    program_id   VARCHAR(10)  NOT NULL DEFAULT 'bms',
    user_id      INT          NULL,
    name         VARCHAR(100) NOT NULL,
    nickname     VARCHAR(100) NULL,
    email        VARCHAR(150) NULL,
    stage_name   VARCHAR(100) NULL,
    birth_place  VARCHAR(100) NULL,
    birth_date   DATE         NULL,
    domicile     VARCHAR(150) NULL,
    phone        VARCHAR(30)  NULL,
    year_join    VARCHAR(10)   NULL,
    field_of_work VARCHAR(100) NULL,
    role         ENUM('Sopran','Alto','Tenor','Bass') NULL,
    section      VARCHAR(100) NULL,
    join_date    DATE         NULL,
    status       ENUM('active','passive') NOT NULL DEFAULT 'active',
    performances INT          NOT NULL DEFAULT 0,
    avatar       VARCHAR(255) NOT NULL DEFAULT 'https://voca-land.sgp1.cdn.digitaloceanspaces.com/0/1757684222527/9465e2e8.jpg',
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id),
    FOREIGN KEY (user_id)    REFERENCES users(id)
);

-- ============================================================
-- Migration: BMS concert roster & schedule attendance
-- Run on existing installations that already have schedules.
-- ============================================================
-- CREATE TABLE concert_roster (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     concert_schedule_id INT NOT NULL,
--     member_id INT NOT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     UNIQUE KEY uk_concert_member (concert_schedule_id, member_id),
--     FOREIGN KEY (concert_schedule_id) REFERENCES schedules(id) ON DELETE CASCADE,
--     FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
-- );
-- CREATE TABLE schedule_attendance (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     schedule_id INT NOT NULL,
--     member_id INT NOT NULL,
--     status ENUM('present', 'absent', 'late', 'excused') NOT NULL DEFAULT 'present',
--     recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--     UNIQUE KEY uk_schedule_member (schedule_id, member_id),
--     FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE,
--     FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
-- );
