<?php

namespace App\Http\Controllers;

use App\Core\Database;
use PDO;

class AuthController
{
    private const ALLOWED_ROLES = ['admin', 'manager', 'teacher', 'arranger', 'member', 'composer/arranger', 'guest'];
    private const DEFAULT_ROLE = 'member';

    public function register(): void
    {
        $input = $this->getJsonInput();
        $name = trim($input['name'] ?? '');
        $email = strtolower(trim($input['email'] ?? ''));
        $password = $input['password'] ?? '';

        if ($name === '' || $email === '' || $password === '') {
            $this->sendJson(422, ['error' => 'Name, email and password are required']);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->sendJson(422, ['error' => 'Please provide a valid email address']);
            return;
        }

        if (strlen($password) < 6) {
            $this->sendJson(422, ['error' => 'Password must be at least 6 characters long']);
            return;
        }

        $db = Database::getInstance();
        $role = $this->normalizeRole($input['role'] ?? self::DEFAULT_ROLE, $this->getSupportedRoles($db));

        $stmt = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);

        if ($stmt->fetch()) {
            $this->sendJson(409, ['error' => 'An account with this email already exists']);
            return;
        }

        $token = $this->generateToken();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare(
            'INSERT INTO users (name, email, password, role, api_token, api_token_exp) VALUES (:name, :email, :password, :role, :token, :expires_at)'
        );

        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
            'role' => $role,
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);

        $this->respondWithUser($db, (int) $db->lastInsertId(), $token);
    }

    public function login(): void
    {
        $input = $this->getJsonInput();
        $email = strtolower(trim($input['email'] ?? ''));
        $password = $input['password'] ?? '';

        if ($email === '' || $password === '') {
            $this->sendJson(422, ['error' => 'Email and password are required']);
            return;
        }

        $db = Database::getInstance();

        $stmt = $db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $this->sendJson(401, ['error' => 'Invalid email or password']);
            return;
        }

        $token = $this->generateToken();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

        $updateStmt = $db->prepare('UPDATE users SET api_token = :token, api_token_exp = :expires_at WHERE id = :id');
        $updateStmt->execute([
            'token' => $token,
            'expires_at' => $expiresAt,
            'id' => (int) $user['id'],
        ]);

        $this->respondWithUser($db, (int) $user['id'], $token);
    }

    public function me(): void
    {
        $token = $this->getBearerToken();

        if ($token === '') {
            $this->sendJson(401, ['error' => 'No token provided']);
            return;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT id, name, email, role, created_at FROM users WHERE api_token = :token AND api_token_exp > NOW() LIMIT 1');
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();

        if (!$user) {
            $this->sendJson(401, ['error' => 'Invalid or expired token']);
            return;
        }

        $this->sendJson(200, ['user' => $user]);
    }

    private function getJsonInput(): array
    {
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);

        return is_array($data) ? $data : [];
    }

    private function normalizeRole(string $role, array $supportedRoles): string
    {
        $normalized = strtolower(trim($role));

        if ($normalized === 'composer_arranger' || $normalized === 'composer/arranger') {
            $normalized = 'composer/arranger';
        }

        if (in_array($normalized, self::ALLOWED_ROLES, true) && in_array($normalized, $supportedRoles, true)) {
            return $normalized;
        }

        if (in_array(self::DEFAULT_ROLE, $supportedRoles, true)) {
            return self::DEFAULT_ROLE;
        }

        return $supportedRoles[0] ?? self::DEFAULT_ROLE;
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function respondWithUser(PDO $db, int $userId, string $token): void
    {
        $stmt = $db->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        $this->sendJson(201, [
            'message' => 'Authentication successful',
            'token' => $token,
            'user' => $user,
        ]);
    }

    private function getBearerToken(): string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return '';
        }

        return $matches[1] ?? '';
    }

    private function getSupportedRoles(PDO $db): array
    {
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'role'");
        $column = $stmt->fetch();

        if (!$column || empty($column['Type'])) {
            return [self::DEFAULT_ROLE];
        }

        if (!preg_match("/^enum\((.*)\)$/", $column['Type'], $matches)) {
            return self::ALLOWED_ROLES;
        }

        preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $matches[1], $roleMatches);
        $roles = array_map(
            static fn (string $role): string => stripcslashes($role),
            $roleMatches[1] ?? []
        );

        return $roles ?: [self::DEFAULT_ROLE];
    }

    private function sendJson(int $statusCode, array $payload): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }
}
