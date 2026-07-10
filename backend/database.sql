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
    role ENUM('admin', 'manager', 'singers_manager', 'teacher', 'arranger', 'member') DEFAULT 'member',
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
    venue VARCHAR(150),
    concert_code VARCHAR(50),
    description TEXT,
    banner_url VARCHAR(500) NULL DEFAULT NULL COMMENT 'Optional banner image URL, shown on homepage slideshow for concert type schedules',
    is_open_register TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = registration is open for this concert schedule',
    is_redirect_url TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = Register Now button redirects to an external URL',
    redirect_url VARCHAR(500) NULL DEFAULT NULL COMMENT 'External URL used when is_redirect_url = 1',
    audience_capacity INT NULL DEFAULT NULL COMMENT 'Max audience registrations for this concert; NULL = unlimited',
    is_seat_assign TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = this concert uses seat selection; guests pick a seat on registration',
    seat_rows INT UNSIGNED NULL DEFAULT NULL COMMENT 'Number of rows in the seating layout (e.g. 10)',
    seat_columns INT UNSIGNED NULL DEFAULT NULL COMMENT 'Number of columns/seats per row in the seating layout (e.g. 20)',
    seat_layout_id VARCHAR(80) NULL DEFAULT NULL COMMENT 'ID of the predefined seating layout from ConcertLayouts.js',
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
    avatar_url   VARCHAR(255) NOT NULL DEFAULT 'https://voca-land.sgp1.cdn.digitaloceanspaces.com/0/1757684222527/9465e2e8.jpg',
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id),
    FOREIGN KEY (user_id)    REFERENCES users(id)
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

CREATE TABLE IF NOT EXISTS concert_rehearsals (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    concert_schedule_id INT NOT NULL COMMENT 'FK to schedules.id where type=concert',
    rehearsal_id        INT NOT NULL COMMENT 'FK to schedules.id where type=practice',
    sort_order          TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_concert_rehearsal (concert_schedule_id, rehearsal_id),
    FOREIGN KEY (concert_schedule_id) REFERENCES schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (rehearsal_id)        REFERENCES schedules(id) ON DELETE CASCADE
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

-- Custom seat layouts table
CREATE TABLE seat_layouts (
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

-- Seat holds table
CREATE TABLE seat_holds (
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
    schedule_id INT NULL DEFAULT NULL COMMENT 'FK to schedules.id (type=concert) this registration belongs to',
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    concert_title VARCHAR(150) NOT NULL,
    ticket_quantity INT NOT NULL DEFAULT 1,
    seat_number VARCHAR(20) NULL DEFAULT NULL COMMENT 'Chosen seat label, e.g. "A5" or "C12". NULL for non-seated concerts.',
    notes TEXT,
    qr_code VARCHAR(100) DEFAULT NULL COMMENT 'Format: {concertCode}_{id}_{timestamp}_{rand4}',
    attended_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Set when the ticket QR is scanned at the door',
    send_email_status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id)
);

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
    FOREIGN KEY (program_id) REFERENCES programs(id),
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE SET NULL
);

-- Index to make per-concert count queries fast
CREATE INDEX idx_ca_schedule_id ON concert_audiences (schedule_id);

-- Index for fast seat availability lookup
CREATE INDEX idx_ca_schedule_seat ON concert_audiences (schedule_id, seat_number);

-- Library — Sheet Music
CREATE TABLE library_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    composer VARCHAR(150) NOT NULL,
    arranger VARCHAR(150) DEFAULT '',
    genre VARCHAR(50) NOT NULL,
    difficulty ENUM('Beginner','Intermediate','Advanced') NOT NULL DEFAULT 'Intermediate',
    pages INT UNSIGNED NOT NULL DEFAULT 0,
    file_url VARCHAR(500) DEFAULT '',
    thumbnail VARCHAR(500) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Library — Costumes
CREATE TABLE library_costumes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    category VARCHAR(100) NOT NULL,
    size VARCHAR(50) NOT NULL,
    item_condition ENUM('New','Excellent','Good','Fair') NOT NULL DEFAULT 'Good',
    last_used DATE DEFAULT NULL,
    notes TEXT DEFAULT '',
    image VARCHAR(500) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert initial programs
INSERT INTO programs (id, name, description, icon) VALUES
('trms', 'TRMS', 'The Resonanz Music Studio', 'bi-music-note-beamed'),
('bms', 'BMS', 'Batavia Madrigal Singers', 'bi-people-fill'),
('jco', 'JCO', 'Jakarta Concert Orchestra', 'bi-vinyl-fill'),
('trcc', 'TRCC', 'The Resonanz Children Choir', 'bi-trophy-fill');

-- Library seed data — Scores
INSERT INTO library_scores (id, title, composer, arranger, genre, difficulty, pages, thumbnail) VALUES
(1, 'Ave Maria', 'Giulio Caccini', 'John Rutter', 'Sacred', 'Intermediate', 6, 'https://placehold.co/400x560/7f2432/c8a45d?text=Ave+Maria'),
(2, 'Bohemian Rhapsody', 'Freddie Mercury', 'Mark Brymer', 'Pop', 'Advanced', 18, 'https://placehold.co/400x560/1d2433/c8a45d?text=Bohemian+Rhapsody'),
(3, 'Canon in D', 'Johann Pachelbel', 'Robert Long', 'Classical', 'Beginner', 4, 'https://placehold.co/400x560/c8a45d/1d2433?text=Canon+in+D'),
(4, 'Over the Rainbow', 'Harold Arlen', 'Roger Emerson', 'Jazz', 'Intermediate', 8, 'https://placehold.co/400x560/6d8175/fffdf8?text=Over+the+Rainbow'),
(5, 'Gamelan Gong Kebyar', 'Traditional', 'I Wayan Beratha', 'Traditional', 'Advanced', 24, 'https://placehold.co/400x560/7f2432/eadcc2?text=Gamelan'),
(6, 'Hallelujah', 'Leonard Cohen', 'Deke Sharon', 'Contemporary', 'Intermediate', 10, 'https://placehold.co/400x560/10131f/c8a45d?text=Hallelujah'),
(7, 'Largo al factotum', 'Gioachino Rossini', 'Luigi Bassi', 'Classical', 'Advanced', 22, 'https://placehold.co/400x560/7f2432/fffdf8?text=Largo'),
(8, 'Take Five', 'Paul Desmond', 'Dave Brubeck', 'Jazz', 'Advanced', 12, 'https://placehold.co/400x560/1d2433/eadcc2?text=Take+Five'),
(9, 'Kampuang Nan Jauh Di Mato', 'Traditional', 'A. Malik', 'Traditional', 'Beginner', 3, 'https://placehold.co/400x560/c8a45d/10131f?text=Kampuang+Nan+Jauh'),
(10, 'Viva La Vida', 'Coldplay', 'Audrey Snyder', 'Pop', 'Intermediate', 11, 'https://placehold.co/400x560/6d8175/c8a45d?text=Viva+La+Vida'),
(11, 'Panis Angelicus', 'César Franck', 'John Leavitt', 'Sacred', 'Intermediate', 7, 'https://placehold.co/400x560/7f2432/eadcc2?text=Panis+Angelicus'),
(12, 'Killing Me Softly', 'Charles Fox', 'Deke Sharon', 'Contemporary', 'Intermediate', 9, 'https://placehold.co/400x560/10131f/eadcc2?text=Killing+Me+Softly');

-- Library seed data — Costumes
INSERT INTO library_costumes (id, name, category, size, item_condition, last_used, notes, image) VALUES
(1, 'Concert Tuxedo — Black', 'Tuxedo', 'L', 'Good', '2025-12-01', 'Includes bow tie', 'https://placehold.co/300x400/10131f/c8a45d?text=Tuxedo+Black'),
(2, 'Batik Traditional — Gold', 'Traditional', 'M', 'Excellent', '2025-11-15', 'Javanese batik pattern', 'https://placehold.co/300x400/c8a45d/10131f?text=Batik+Gold'),
(3, 'Evening Gown — Burgundy', 'Gown', 'S', 'Fair', '2025-10-20', 'Minor hem repair needed', 'https://placehold.co/300x400/7f2432/eadcc2?text=Gown+Burgundy'),
(4, 'Choir Robe — Maroon', 'Robe', 'XL', 'Good', '2025-09-10', 'Standard choir robe', 'https://placehold.co/300x400/7f2432/c8a45d?text=Choir+Robe'),
(5, 'Batik Contemporary — Blue', 'Traditional', 'L', 'Excellent', '2025-11-28', 'Modern batik design', 'https://placehold.co/300x400/1d2433/eadcc2?text=Batik+Blue'),
(6, 'White Tuxedo Jacket', 'Tuxedo', 'M', 'Good', '2025-08-05', 'Cream white', 'https://placehold.co/300x400/fffdf8/10131f?text=White+Tuxedo'),
(7, 'Concert Dress — Black', 'Gown', 'M', 'Excellent', '2025-12-10', 'Floor-length', 'https://placehold.co/300x400/10131f/eadcc2?text=Dress+Black'),
(8, 'Songket Sarong', 'Traditional', 'One Size', 'Good', '2025-07-22', 'Palembang songket', 'https://placehold.co/300x400/c8a45d/7f2432?text=Songket'),
(9, 'Choir Robe — Navy', 'Robe', 'XXL', 'Fair', '2025-06-30', 'Fading at collar', 'https://placehold.co/300x400/1d2433/eadcc2?text=Choir+Robe+Navy'),
(10, 'Kasual Batik — Red', 'Traditional', 'M', 'Excellent', '2025-11-05', 'Casual concert wear', 'https://placehold.co/300x400/7f2432/c8a45d?text=Batik+Red'),
(11, 'Black Pants — Formal', 'Tuxedo', 'L', 'Good', '2025-10-12', 'Wrinkle-free fabric', 'https://placehold.co/300x400/10131f/fffdf8?text=Formal+Pants'),
(12, 'Corsage — Gold Accent', 'Accessory', 'One Size', 'New', NULL, 'For formal events', 'https://placehold.co/300x400/c8a45d/1d2433?text=Gold+Corsage');
