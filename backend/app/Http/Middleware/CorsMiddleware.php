<?php

namespace App\Http\Middleware;

class CorsMiddleware
{
    public function handle(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');

        header("Content-Security-Policy: script-src 'self' 'unsafe-eval' https://app.sandbox.midtrans.com;");

        // Cache static assets (images, fonts, etc.) for 30 days
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($uri, PHP_URL_PATH) ?? '';

        $staticExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'woff', 'woff2', 'css', 'js'];
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($ext, $staticExtensions)) {
            header('Cache-Control: public, max-age=2592000, immutable');
        } elseif (str_starts_with($path, '/api/')) {
            // Cache GET API responses briefly for repeat requests
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                header('Cache-Control: public, max-age=30, stale-while-revalidate=300');
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
