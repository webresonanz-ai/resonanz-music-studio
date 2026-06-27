<?php

namespace App\Http\Middleware;

class JsonMiddleware
{
    public function handle(): void
    {
        if (
            $_SERVER['REQUEST_METHOD'] !== 'GET' &&
            isset($_SERVER['CONTENT_TYPE']) &&
            strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false
        ) {
            $input = file_get_contents('php://input');
            $decoded = json_decode($input, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Invalid JSON payload']);
                exit;
            }

            $_POST = array_merge($_POST, $decoded ?: []);
        }
    }
}