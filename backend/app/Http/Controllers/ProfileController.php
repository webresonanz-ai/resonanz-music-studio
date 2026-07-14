<?php

namespace App\Http\Controllers;

use App\Core\Database;
use App\Core\Mail;
use PDO;

class ProfileController
{
    private const AVATAR_UPLOAD_DIR = __DIR__ . '/../../../public/uploads/avatars/';
    private const ALLOWED_MIME = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    private const AVATAR_MAX_SIZE = 2 * 1024 * 1024;

    public function show(): void
    {
        $user = $this->authenticatedUser();
        if (!$user)
            return;

        $this->sendJson(200, [
            'user' => [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'username' => $user['username'],
                'email' => $user['email'],
                'avatar_url' => $user['avatar_url'],
                'role' => $user['role'],
                'email_verified_at' => $user['email_verified_at'],
                'created_at' => $user['created_at'],
            ],
        ]);
    }

    public function update(): void
    {
        $user = $this->authenticatedUser();
        if (!$user)
            return;

        $input = $this->getJsonInput();
        $db = Database::getInstance();

        $fields = [];
        $params = [];

        if (isset($input['name'])) {
            $name = trim($input['name']);
            if ($name === '') {
                $this->sendJson(422, ['error' => 'Name cannot be empty']);
                return;
            }
            $fields[] = 'name = :name';
            $params['name'] = $name;
        }

        if (isset($input['username'])) {
            $username = trim($input['username']);
            if ($username === '') {
                $this->sendJson(422, ['error' => 'Username cannot be empty']);
                return;
            }
            if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
                $this->sendJson(422, ['error' => 'Username must be 3-50 characters, letters, numbers, and underscores only']);
                return;
            }
            $stmt = $db->prepare('SELECT id FROM users WHERE username = :username AND id != :id LIMIT 1');
            $stmt->execute(['username' => $username, 'id' => $user['id']]);
            if ($stmt->fetch()) {
                $this->sendJson(409, ['error' => 'Username already taken']);
                return;
            }
            $fields[] = 'username = :username';
            $params['username'] = $username;
        }

        if (isset($input['avatar_url'])) {
            $url = trim($input['avatar_url']);
            if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
                $this->sendJson(422, ['error' => 'Invalid avatar URL']);
                return;
            }
            $fields[] = 'avatar_url = :avatar_url';
            $params['avatar_url'] = $url ?: null;
        }

        if (empty($fields)) {
            $this->sendJson(422, ['error' => 'Nothing to update']);
            return;
        }

        $params['id'] = $user['id'];
        $db->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id')
            ->execute($params);

        $stmt = $db->prepare('SELECT id, name, username, email, avatar_url, role, email_verified_at, created_at FROM users WHERE id = :id');
        $stmt->execute(['id' => $user['id']]);
        $updated = $stmt->fetch();

        $this->sendJson(200, ['message' => 'Profile updated', 'user' => $updated]);
    }

    public function uploadAvatar(): void
    {
        $user = $this->authenticatedUser();
        if (!$user)
            return;

        if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $this->sendJson(422, ['error' => 'No file uploaded or upload error']);
            return;
        }

        $file = $_FILES['avatar'];

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            $this->sendJson(422, ['error' => 'Only JPEG, PNG, and WebP images are allowed']);
            return;
        }

        if ($file['size'] > self::AVATAR_MAX_SIZE) {
            $this->sendJson(422, ['error' => 'File size exceeds the 2 MB limit']);
            return;
        }

        if (!is_dir(self::AVATAR_UPLOAD_DIR)) {
            mkdir(self::AVATAR_UPLOAD_DIR, 0755, true);
        }

        $ext = match ($mime) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
        $filename = 'avatar_' . $user['id'] . '_' . uniqid('', true) . '.' . $ext;
        $destination = self::AVATAR_UPLOAD_DIR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $this->sendJson(500, ['error' => 'Failed to save uploaded file']);
            return;
        }

        $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost:8000', '/');
        $publicUrl = $appUrl . '/uploads/avatars/' . $filename;

        $db = Database::getInstance();
        $db->prepare('UPDATE users SET avatar_url = :url WHERE id = :id')
            ->execute(['url' => $publicUrl, 'id' => $user['id']]);

        $this->sendJson(200, ['url' => $publicUrl]);
    }

    public function sendVerification(): void
    {
        $user = $this->authenticatedUser();
        if (!$user)
            return;

        if ($user['email_verified_at'] !== null) {
            $this->sendJson(400, ['error' => 'Email already verified']);
            return;
        }

        $db = Database::getInstance();

        $db->prepare('DELETE FROM email_verifications WHERE user_id = :uid AND used_at IS NULL')
            ->execute(['uid' => $user['id']]);

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $db->prepare('INSERT INTO email_verifications (user_id, token, email, expires_at) VALUES (:uid, :token, :email, :expires)')
            ->execute([
                'uid' => $user['id'],
                'token' => $token,
                'email' => $user['email'],
                'expires' => $expiresAt,
            ]);

        $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost:8000', '/');
        $verifyUrl = $appUrl . '/api/profile/verify-email/' . $token;

        $subject = 'Verify your email – Resonanz Music Studio';
        $textBody = "Hello {$user['name']},\n\n"
            . "Please verify your email by clicking the link below:\n"
            . "$verifyUrl\n\n"
            . "This link expires in 24 hours.\n\n"
            . "If you did not request this, please ignore this email.\n\n"
            . "Best regards,\nResonanz Music Studio";

        $htmlBody = '<p>Hello <strong>' . htmlspecialchars($user['name']) . "</strong>,</p>\n"
            . '<p>Please verify your email by clicking the button below:</p>\n'
            . '<p style="text-align:center;margin:30px 0;">'
            . '<a href="' . htmlspecialchars($verifyUrl) . '" '
            . 'style="display:inline-block;padding:12px 32px;background:#c8a45d;color:#10131f;'
            . 'text-decoration:none;border-radius:6px;font-weight:700;">Verify Email</a></p>\n'
            . '<p>Or copy this link into your browser:</p>\n'
            . '<p style="word-break:break-all;font-size:0.85em;color:#999;">'
            . htmlspecialchars($verifyUrl) . "</p>\n"
            . '<p><small>This link expires in 24 hours. If you did not request this, please ignore this email.</small></p>\n'
            . '<p>Best regards,<br>Resonanz Music Studio</p>';

        $mail = new Mail($user['email'], $subject, $textBody, null, '', $htmlBody);
        $ok = $mail->send();

        if (!$ok) {
            $this->sendJson(500, ['error' => 'Failed to send verification email. Please try again later.']);
            return;
        }

        $this->sendJson(200, ['message' => 'Verification email sent']);
    }

    public function verifyEmail(string $token): void
    {
        $db = Database::getInstance();

        $stmt = $db->prepare(
            'SELECT ev.*, u.email_verified_at
             FROM email_verifications ev
             JOIN users u ON u.id = ev.user_id
             WHERE ev.token = :token AND ev.used_at IS NULL AND ev.expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();

        if (!$row) {
            $this->redirectWithMessage('Verification link is invalid or has expired');
            return;
        }

        if ($row['email_verified_at'] !== null) {
            $db->prepare('UPDATE email_verifications SET used_at = NOW() WHERE id = :id')
                ->execute(['id' => $row['id']]);
            $this->redirectWithMessage('Email already verified');
            return;
        }

        $db->beginTransaction();
        try {
            $db->prepare('UPDATE users SET email_verified_at = NOW() WHERE id = :id')
                ->execute(['id' => $row['user_id']]);
            $db->prepare('UPDATE email_verifications SET used_at = NOW() WHERE id = :id')
                ->execute(['id' => $row['id']]);
            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            $this->redirectWithMessage('Verification failed due to a server error');
            return;
        }

        $this->redirectWithMessage('Email verified successfully', true);
    }

    private function authenticatedUser(): ?array
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = '';

        if (!empty($authHeader) && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1] ?? '';
        }

        if (empty($token)) {
            $this->sendJson(401, ['error' => 'No token provided']);
            return null;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT id, name, username, email, avatar_url, role, email_verified_at, created_at
             FROM users WHERE api_token = :token AND api_token_exp > NOW() LIMIT 1'
        );
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();

        if (!$user) {
            $this->sendJson(401, ['error' => 'Invalid or expired token']);
            return null;
        }

        return $user;
    }

    private function redirectWithMessage(string $message, bool $success = false): void
    {
        header('Content-Type: text/html; charset=utf-8');
        $icon = $success ? '✓' : '✗';
        $color = $success ? '#6bcf9a' : '#e86868';
        $bg = $success ? 'rgba(76,175,125,0.1)' : 'rgba(220,53,69,0.1)';
        $border = $success ? 'rgba(76,175,125,0.2)' : 'rgba(220,53,69,0.2)';
        $title = $success ? 'Email Verified' : 'Verification Failed';

        $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost:8000', '/');

        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$title} – Resonanz Music Studio</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    min-height: 100vh;
    display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #1a1f30 0%, #232b40 30%, #1a1f30 60%, #151a28 100%);
    color: #e8e4f0;
    padding: 2rem 1rem;
  }
  .card {
    width: min(100%, 440px);
    border-radius: 16px; padding: 2.5rem 2rem;
    border: 1px solid rgba(234,220,194,0.12);
    background: linear-gradient(135deg, rgba(200,164,93,0.1), transparent 50%),
                linear-gradient(180deg, #1a1f30 0%, #111420 100%);
    box-shadow: 0 1px 0 rgba(255,255,255,0.04) inset, 0 20px 60px rgba(10,10,18,0.35);
    text-align: center;
  }
  .icon {
    display: inline-flex; align-items: center; justify-content: center;
    width: 64px; height: 64px; border-radius: 50%;
    background: {$bg}; border: 2px solid {$border};
    color: {$color}; font-size: 2rem; font-weight: 700;
    margin-bottom: 1.25rem;
  }
  h1 { font-size: 1.35rem; color: #c8a45d; margin-bottom: 0.75rem; }
  p { font-size: 0.9rem; color: rgba(234,220,194,0.7); line-height: 1.6; margin-bottom: 1.5rem; }
  .btn {
    display: inline-block; padding: 0.7rem 1.8rem;
    background: #c8a45d; color: #10131f;
    text-decoration: none; border-radius: 10px; font-weight: 700;
    transition: background 0.2s;
  }
  .btn:hover { background: #dfc280; }
</style>
</head>
<body>
<div class="card">
  <div class="icon">{$icon}</div>
  <h1>{$title}</h1>
  <p>{$message}</p>
  <a class="btn" href="{$appUrl}/profile">Go to Profile</a>
</div>
</body>
</html>
HTML;
        exit;
    }

    private function getJsonInput(): array
    {
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);
        return is_array($data) ? $data : [];
    }

    private function sendJson(int $statusCode, array $payload): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }
}
