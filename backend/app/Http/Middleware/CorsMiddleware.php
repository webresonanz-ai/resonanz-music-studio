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

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}