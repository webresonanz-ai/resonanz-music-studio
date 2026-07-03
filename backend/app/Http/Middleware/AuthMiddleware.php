<?php

namespace App\Http\Middleware;

use App\Core\Database;

class AuthMiddleware
{
    public function handle(): void
    {
        // Try Authorization header first
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = '';
        
        if (!empty($authHeader) && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1] ?? '';
        }
        
        // Fallback to query parameter for direct browser access (e.g. PDF downloads)
        if (empty($token)) {
            $token = $_GET['token'] ?? '';
        }
        
        if (empty($token)) {
            $this->unauthorized('No token provided');
        }
        
        if (!$this->validateToken($token)) {
            $this->unauthorized('Invalid or expired token');
        }
    }

    private function validateToken(string $token): bool
    {
        $stmt = Database::getInstance()
            ->prepare('SELECT * FROM users WHERE api_token = :token AND api_token_exp > NOW()');
        $stmt->execute(['token' => $token]);
        return (bool) $stmt->fetch();
    }

    private function unauthorized(string $message): void
    {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }
}