<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Http\Middleware\CorsMiddleware;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\JsonMiddleware;
use App\Http\Middleware\RoleMiddleware;
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
$router->get('/api/profile/verify-email/{token}', 'App\Http\Controllers\ProfileController@verifyEmail');

// Protected profile routes
$router->group(['middleware' => [AuthMiddleware::class]], function ($router) {
    $router->get('/api/profile', 'App\Http\Controllers\ProfileController@show');
    $router->post('/api/profile/update', 'App\Http\Controllers\ProfileController@update');
    $router->post('/api/profile/upload-avatar', 'App\Http\Controllers\ProfileController@uploadAvatar');
    $router->post('/api/profile/send-verification', 'App\Http\Controllers\ProfileController@sendVerification');
});

// Program-specific public routes
$router->get('/api/trms/teachers', 'App\Http\Controllers\Trms\TeacherController@index');
$router->get('/api/trms/courses', 'App\Http\Controllers\Trms\CourseController@index');
$router->get('/api/trms/news', 'App\Http\Controllers\Trms\NewsController@index');
$router->get('/api/trms/schedule', 'App\Http\Controllers\Trms\ScheduleController@index');
$router->post('/api/trms/schedule', 'App\Http\Controllers\Trms\ScheduleController@store');
$router->post('/api/trms/schedule/{id}', 'App\Http\Controllers\Trms\ScheduleController@update');
$router->post('/api/trms/schedule/{id}/delete', 'App\Http\Controllers\Trms\ScheduleController@destroy');
$router->post('/api/trms/contact', 'App\Http\Controllers\Trms\ContactController@store');
$router->post('/api/trms/upload/banner', 'App\Http\Controllers\Trms\UploadController@bannerUpload');
$router->post('/api/trms/concert/registration', 'App\Http\Controllers\Trms\ConcertAudienceController@store');
$router->post('/api/trms/concert/scan', 'App\Http\Controllers\Trms\ConcertAudienceController@scan');
$router->get('/api/trms/concert/seats/{scheduleId}', 'App\Http\Controllers\Trms\ConcertAudienceController@seats');
$router->get('/api/trms/seat-layouts', 'App\Http\Controllers\Trms\SeatLayoutController@index');
$router->get('/api/trms/seat-layouts/{key}', 'App\Http\Controllers\Trms\SeatLayoutController@show');
$router->post('/api/trms/seat-layouts', 'App\Http\Controllers\Trms\SeatLayoutController@store');
$router->post('/api/trms/seat-holds', 'App\Http\Controllers\Trms\SeatHoldController@hold');
$router->post('/api/trms/seat-holds/release', 'App\Http\Controllers\Trms\SeatHoldController@release');
$router->get('/api/trms/seat-holds/{scheduleId}', 'App\Http\Controllers\Trms\SeatHoldController@myHolds');
$router->get('/api/trms/concert/audiences', 'App\Http\Controllers\Trms\ConcertAudienceController@index');
$router->get('/api/trms/concert/audiences/concerts', 'App\Http\Controllers\Trms\ConcertAudienceController@concerts');
$router->get('/api/trms/concert/audiences/{id}', 'App\Http\Controllers\Trms\ConcertAudienceController@show');
$router->post('/api/trms/concert/audiences/{id}', 'App\Http\Controllers\Trms\ConcertAudienceController@update');
$router->post('/api/trms/concert/audiences/{id}/delete', 'App\Http\Controllers\Trms\ConcertAudienceController@destroy');
$router->post('/api/trms/concert/audiences/{id}/resend-email', 'App\Http\Controllers\Trms\ConcertAudienceController@resendEmail');

// Ticket PDF — admin & manager only
RoleMiddleware::$roles = ['admin', 'manager'];
$router->group(['middleware' => [AuthMiddleware::class, RoleMiddleware::class]], function ($router) {
    $router->post('/api/trms/news', 'App\Http\Controllers\Trms\NewsController@store');
    $router->post('/api/trms/news/{id}', 'App\Http\Controllers\Trms\NewsController@update');
    $router->post('/api/trms/news/{id}/delete', 'App\Http\Controllers\Trms\NewsController@destroy');
    $router->get('/api/trms/concert/ticket/{id}', 'App\Http\Controllers\Trms\ConcertAudienceController@downloadTicket');
    $router->post('/api/trms/concert/send-bulk-email', 'App\Http\Controllers\Trms\ConcertAudienceController@sendBulkEmail');
});

$router->get('/api/bms/events', 'App\Http\Controllers\Bms\EventController@index');
$router->get('/api/bms/members', 'App\Http\Controllers\Bms\MemberController@index');
$router->get('/api/bms/about-us', 'App\Http\Controllers\Bms\AboutController@index');
$router->get('/api/bms/attendance/concerts', 'App\Http\Controllers\Bms\AttendanceController@concerts');
$router->get('/api/bms/attendance/concerts/{id}', 'App\Http\Controllers\Bms\AttendanceController@show');
$router->get('/api/bms/attendance/concerts/{id}/by-date/{date}', 'App\Http\Controllers\Bms\AttendanceController@byDate');

// Protected BMS member CRUD routes
$router->group(['middleware' => [AuthMiddleware::class]], function ($router) {
    $router->get('/api/bms/members/{id}', 'App\Http\Controllers\Bms\MemberController@show');
    $router->post('/api/bms/members', 'App\Http\Controllers\Bms\MemberController@store');
    $router->post('/api/bms/members/{id}', 'App\Http\Controllers\Bms\MemberController@update');
    $router->post('/api/bms/members/{id}/delete', 'App\Http\Controllers\Bms\MemberController@destroy');
});

$router->get('/api/jco/orchestra/members', 'App\Http\Controllers\Jco\OrchestraMemberController@index');
$router->get('/api/jco/concerts', 'App\Http\Controllers\Jco\ConcertController@index');
$router->get('/api/jco/gallery', 'App\Http\Controllers\Jco\GalleryController@index');
$router->get('/api/jco/about-us', 'App\Http\Controllers\Jco\AboutController@index');
$router->post('/api/jco/contact', 'App\Http\Controllers\Jco\ContactController@store');

$router->get('/api/trcc/achievements', 'App\Http\Controllers\Trcc\AchievementController@index');
$router->get('/api/trcc/testimonials', 'App\Http\Controllers\Trcc\TestimonialController@index');
$router->get('/api/trcc/about-us', 'App\Http\Controllers\Trcc\AboutController@index');
$router->post('/api/trcc/contact', 'App\Http\Controllers\Trcc\ContactController@store');

// Library routes (public)
$router->get('/api/library/scores', 'App\Http\Controllers\Library\ScoreController@index');
$router->get('/api/library/scores/{id}', 'App\Http\Controllers\Library\ScoreController@show');
$router->get('/api/library/costumes', 'App\Http\Controllers\Library\CostumeController@index');
$router->get('/api/library/costumes/{id}', 'App\Http\Controllers\Library\CostumeController@show');

// Library — Order routes (authenticated users)
$router->group(['middleware' => [AuthMiddleware::class]], function ($router) {
    $router->post('/api/library/orders', 'App\Http\Controllers\Library\OrderController@store');
    $router->get('/api/library/orders', 'App\Http\Controllers\Library\OrderController@index');
    $router->get('/api/library/orders/{id}', 'App\Http\Controllers\Library\OrderController@show');
    $router->post('/api/library/orders/{id}/snap-token', 'App\Http\Controllers\Library\OrderController@snapToken');
    $router->post('/api/library/orders/{id}/cancel', 'App\Http\Controllers\Library\OrderController@cancel');
});

// Midtrans payment notification webhook (public, no auth)
$router->post('/api/library/orders/notification', 'App\Http\Controllers\Library\OrderController@notification');

// Library — Admin routes (manager_scores / admin / manager)
$router->group(['middleware' => [AuthMiddleware::class]], function ($router) {
    $router->get('/api/library/admin/orders', 'App\Http\Controllers\Library\AdminController@orders');
    $router->get('/api/library/admin/profit-shares', 'App\Http\Controllers\Library\AdminController@profitShares');
    $router->post('/api/library/admin/profit-shares', 'App\Http\Controllers\Library\AdminController@profitShares');
    $router->get('/api/library/admin/creator-profit/{userId}', 'App\Http\Controllers\Library\AdminController@creatorProfit');
    $router->post('/api/library/admin/creator-payout', 'App\Http\Controllers\Library\AdminController@recordPayout');
    $router->get('/api/library/admin/creator-payouts/{userId}', 'App\Http\Controllers\Library\AdminController@payoutHistory');
});

// Protected BMS attendance routes — admin, manager, singers_manager only
RoleMiddleware::$roles = ['admin', 'manager', 'singers_manager'];
$router->group(['middleware' => [AuthMiddleware::class, RoleMiddleware::class]], function ($router) {
    $router->post('/api/bms/attendance/roster', 'App\Http\Controllers\Bms\AttendanceController@updateRoster');
    $router->post('/api/bms/attendance/rehearsals', 'App\Http\Controllers\Bms\AttendanceController@updateRehearsals');
    $router->post('/api/bms/attendance/record', 'App\Http\Controllers\Bms\AttendanceController@record');
    $router->post('/api/bms/attendance/record/bulk', 'App\Http\Controllers\Bms\AttendanceController@recordBulk');
});

// Protected Library CRUD — any authenticated user (role check done in controller)
$router->group(['middleware' => [AuthMiddleware::class]], function ($router) {
    $router->post('/api/library/scores', 'App\Http\Controllers\Library\ScoreController@store');
    $router->post('/api/library/scores/{id}', 'App\Http\Controllers\Library\ScoreController@update');
    $router->post('/api/library/scores/{id}/delete', 'App\Http\Controllers\Library\ScoreController@destroy');
    $router->post('/api/library/scores/{id}/upload-pdf', 'App\Http\Controllers\Library\ScoreController@uploadPdf');
    $router->post('/api/library/costumes', 'App\Http\Controllers\Library\CostumeController@store');
    $router->post('/api/library/costumes/{id}', 'App\Http\Controllers\Library\CostumeController@update');
    $router->post('/api/library/costumes/{id}/delete', 'App\Http\Controllers\Library\CostumeController@destroy');
});// Run the application
$router->dispatch();
