# Resonanz Music Studio - Backend API

PHP REST API backend with MySQL database for Resonanz Music Studio.

## Requirements
- PHP 8.1+
- MySQL 5.7+ / MariaDB 10.4+
- Composer

## Installation

1. Install dependencies:
```bash
cd backend
composer install
```

2. Create database and import schema:
```bash
mysql -u root -p
CREATE DATABASE resonanz_music_studio;
exit
mysql -u root -p resonanz_music_studio < database.sql
```

3. Configure environment in `.env`:
```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=resonanz_music_studio
DB_USER=root
DB_PASS=your_password

APP_ENV=development
APP_DEBUG=true
APP_KEY=your_secret_key
```

## Directory Structure

```
backend/
├── app/
│   ├── Core/
│   │   ├── Database.php      # PDO database wrapper
│   │   ├── Model.php         # Base model class
│   │   └── Router.php        # HTTP router
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── HomeController.php
│   │   │   ├── ProgramController.php
│   │   │   ├── Trms/         # TRMS program controllers
│   │   │   ├── Bms/          # BMS program controllers
│   │   │   ├── Jco/          # JCO program controllers
│   │   │   └── Trcc/         # TRCC program controllers
│   │   └── Middleware/
│   │       ├── CorsMiddleware.php    # CORS handling
│   │       ├── AuthMiddleware.php    # JWT authentication
│   │       ├── JsonMiddleware.php    # JSON request parsing
│   │       └── RequestMiddleware.php # Input validation
│   └── Models/
│       ├── Program.php
│       ├── Teacher.php
│       ├── Course.php
│       ├── Event.php
│       ├── Member.php
│       ├── News.php
│       ├── Concert.php
│       ├── Gallery.php
│       ├── Achievement.php
│       └── Testimonial.php
├── public/
│   └── index.php            # Entry point
├── database.sql             # Database schema
├── composer.json
└── .env
```

## API Endpoints

### Public Routes
- `GET /` - Welcome message
- `GET /programs` - List all programs
- `GET /trms/teachers` - TRMS teachers
- `GET /trms/courses` - TRMS courses
- `GET /trms/news` - TRMS news
- `GET /trms/schedule` - TRMS schedules
- `POST /trms/contact` - TRMS contact form

- `GET /bms/events` - BMS events
- `GET /bms/members` - BMS members
- `GET /bms/about-us` - BMS about info

- `GET /jco/orchestra/members` - JCO orchestra members
- `GET /jco/concerts` - JCO concerts
- `GET /jco/gallery` - JCO gallery
- `GET /jco/about-us` - JCO about info
- `POST /jco/contact` - JCO contact form

- `GET /trcc/achievements` - TRCC achievements
- `GET /trcc/testimonials` - TRCC testimonials
- `GET /trcc/about-us` - TRCC about info
- `POST /trcc/contact` - TRCC contact form

### Protected Routes (require Bearer token)
- `GET /bms/attendance` - Attendance records
- `POST /bms/attendance` - Record attendance

## Running with PHP Built-in Server

```bash
cd backend/public
php -S localhost:8000
```