<?php

namespace App\Http\Middleware;

use App\Core\Database;

class AuthMiddleware
{
    public function handle(): void
    {
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (empty($token)) {
            $this->unauthorized('No token provided');
        }

        if (!preg_match('/Bearer\s+(.*)$/i', $token, $matches)) {
            $this->unauthorized('Invalid token format');
        }

        $token = $matches[1] ?? '';
        
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