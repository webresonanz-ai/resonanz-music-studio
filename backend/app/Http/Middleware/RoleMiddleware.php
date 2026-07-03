<?php

namespace App\Http\Middleware;

use App\Core\Database;

/**
 * Role-based access middleware.
 *
 * Because the Router instantiates middleware via `new ClassName()` with no
 * constructor arguments, allowed roles are injected via a static property
 * that must be set before the route group is registered:
 *
 *   RoleMiddleware::$roles = ['admin', 'manager', 'singers_manager'];
 *   $router->group(['middleware' => [AuthMiddleware::class, RoleMiddleware::class]], fn($r) => …);
 */
class RoleMiddleware
{
    /** Set this before registering the route group. */
    public static array $roles = [];

    public function handle(): void
    {
        // Token is already validated by AuthMiddleware which runs first.
        // We still need the user's role, so we re-fetch from the token.
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
            $this->deny(401, 'Authentication required');
        }

        $token = $matches[1];

        $stmt = Database::getInstance()->prepare(
            'SELECT role FROM users
             WHERE api_token = :token AND api_token_exp > NOW()
             LIMIT 1'
        );
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();

        if (!$user) {
            $this->deny(401, 'Invalid or expired token');
        }

        if (!empty(self::$roles)) {
            $userRole = strtolower($user['role'] ?? '');
            if (!in_array($userRole, self::$roles, true)) {
                $this->deny(403, 'You do not have permission to perform this action');
            }
        }
    }

    private function deny(int $status, string $message): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }
}
