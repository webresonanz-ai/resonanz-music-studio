<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Http\Middleware\CorsMiddleware;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\JsonMiddleware;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Initialize middleware
$cors = new CorsMiddleware();
$cors->handle();

$json = new JsonMiddleware();
$json->handle();

// Initialize Router
$router = new Router();

// Public routes
$router->get('/api/', 'App\Http\Controllers\HomeController@index');
$router->get('/api/programs', 'App\Http\Controllers\ProgramController@index');
$router->post('/api/auth/register', 'App\Http\Controllers\AuthController@register');
$router->post('/api/auth/login', 'App\Http\Controllers\AuthController@login');
$router->get('/api/auth/me', 'App\Http\Controllers\AuthController@me');

// Program-specific public routes
$router->get('/api/trms/teachers', 'App\Http\Controllers\Trms\TeacherController@index');
$router->get('/api/trms/courses', 'App\Http\Controllers\Trms\CourseController@index');
$router->get('/api/trms/news', 'App\Http\Controllers\Trms\NewsController@index');
$router->get('/api/trms/schedule', 'App\Http\Controllers\Trms\ScheduleController@index');
$router->post('/api/trms/schedule', 'App\Http\Controllers\Trms\ScheduleController@store');
$router->post('/api/trms/schedule/{id}', 'App\Http\Controllers\Trms\ScheduleController@update');
$router->post('/api/trms/schedule/{id}/delete', 'App\Http\Controllers\Trms\ScheduleController@destroy');
$router->post('/api/trms/contact', 'App\Http\Controllers\Trms\ContactController@store');
$router->post('/api/trms/concert/registration', 'App\Http\Controllers\Trms\ConcertAudienceController@store');
$router->get('/api/trms/concert/audiences', 'App\Http\Controllers\Trms\ConcertAudienceController@index');

$router->get('/api/bms/events', 'App\Http\Controllers\Bms\EventController@index');
$router->get('/api/bms/members', 'App\Http\Controllers\Bms\MemberController@index');
$router->get('/api/bms/about-us', 'App\Http\Controllers\Bms\AboutController@index');

$router->get('/api/jco/orchestra/members', 'App\Http\Controllers\Jco\OrchestraMemberController@index');
$router->get('/api/jco/concerts', 'App\Http\Controllers\Jco\ConcertController@index');
$router->get('/api/jco/gallery', 'App\Http\Controllers\Jco\GalleryController@index');
$router->get('/api/jco/about-us', 'App\Http\Controllers\Jco\AboutController@index');
$router->post('/api/jco/contact', 'App\Http\Controllers\Jco\ContactController@store');

$router->get('/api/trcc/achievements', 'App\Http\Controllers\Trcc\AchievementController@index');
$router->get('/api/trcc/testimonials', 'App\Http\Controllers\Trcc\TestimonialController@index');
$router->get('/api/trcc/about-us', 'App\Http\Controllers\Trcc\AboutController@index');
$router->post('/api/trcc/contact', 'App\Http\Controllers\Trcc\ContactController@store');

// Protected API routes
$router->group(['middleware' => [AuthMiddleware::class]], function ($router) {
    $router->get('/api/bms/attendance', 'App\Http\Controllers\Bms\AttendanceController@index');
    $router->post('/api/bms/attendance', 'App\Http\Controllers\Bms\AttendanceController@store');
});

// Run the application
$router->dispatch();
