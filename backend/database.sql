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

-- Attendance table
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    member_id INT NOT NULL,
    status ENUM('present', 'absent', 'late') DEFAULT 'present',
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
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
    FOREIGN KEY (program_id) REFERENCES programs(id)
);

-- Insert initial programs
INSERT INTO programs (id, name, description, icon) VALUES
('trms', 'TRMS', 'The Resonanz Music Studio', 'bi-music-note-beamed'),
('bms', 'BMS', 'Batavia Madrigal Singers', 'bi-people-fill'),
('jco', 'JCO', 'Jakarta Concert Orchestra', 'bi-vinyl-fill'),
('trcc', 'TRCC', 'The Resonanz Children Choir', 'bi-trophy-fill');
