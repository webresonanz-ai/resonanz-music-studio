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
$router->get('/', 'App\Http\Controllers\HomeController@index');
$router->get('/programs', 'App\Http\Controllers\ProgramController@index');

// Program-specific public routes
$router->get('/trms/teachers', 'App\Http\Controllers\Trms\TeacherController@index');
$router->get('/trms/courses', 'App\Http\Controllers\Trms\CourseController@index');
$router->get('/trms/news', 'App\Http\Controllers\Trms\NewsController@index');
$router->get('/trms/schedule', 'App\Http\Controllers\Trms\ScheduleController@index');
$router->post('/trms/contact', 'App\Http\Controllers\Trms\ContactController@store');

$router->get('/bms/events', 'App\Http\Controllers\Bms\EventController@index');
$router->get('/bms/members', 'App\Http\Controllers\Bms\MemberController@index');
$router->get('/bms/about-us', 'App\Http\Controllers\Bms\AboutController@index');

$router->get('/jco/orchestra/members', 'App\Http\Controllers\Jco\OrchestraMemberController@index');
$router->get('/jco/concerts', 'App\Http\Controllers\Jco\ConcertController@index');
$router->get('/jco/gallery', 'App\Http\Controllers\Jco\GalleryController@index');
$router->get('/jco/about-us', 'App\Http\Controllers\Jco\AboutController@index');
$router->post('/jco/contact', 'App\Http\Controllers\Jco\ContactController@store');

$router->get('/trcc/achievements', 'App\Http\Controllers\Trcc\AchievementController@index');
$router->get('/trcc/testimonials', 'App\Http\Controllers\Trcc\TestimonialController@index');
$router->get('/trcc/about-us', 'App\Http\Controllers\Trcc\AboutController@index');
$router->post('/trcc/contact', 'App\Http\Controllers\Trcc\ContactController@store');

// Protected API routes
$router->group(['middleware' => [AuthMiddleware::class]], function($router) {
    $router->get('/bms/attendance', 'App\Http\Controllers\Bms\AttendanceController@index');
    $router->post('/bms/attendance', 'App\Http\Controllers\Bms\AttendanceController@store');
});

// Run the application
$router->dispatch();