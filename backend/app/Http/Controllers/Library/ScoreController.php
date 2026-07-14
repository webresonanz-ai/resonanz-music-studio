<?php

namespace App\Http\Controllers\Library;

use App\Models\LibraryScore;

class ScoreController
{
    private LibraryScore $model;

    public function __construct()
    {
        $this->model = new LibraryScore();
    }

    public function index(): void
    {
        $this->json($this->model->all());
    }

    public function show(string $id): void
    {
        $score = $this->model->find((int) $id);

        if (!$score) {
            $this->json(['error' => 'Score not found'], 404);
            return;
        }

        $this->json($score);
    }

    public function store(): void
    {
        $this->requireRole(['admin', 'manager', 'composer', 'arranger']);

        $data = $this->input();

        if (empty($data['title'])) {
            $this->json(['error' => 'Title is required'], 422);
            return;
        }

        $data['price'] = isset($data['price']) ? (float) $data['price'] : 0;
        $data['created_by'] = $this->getUserId();

        $id = $this->model->create($data);
        $score = $this->model->find($id);

        $this->json(['success' => true, 'message' => 'Score created', 'data' => $score], 201);
    }

    public function update(string $id): void
    {
        $this->requireRole(['admin', 'manager', 'composer', 'arranger']);

        $score = $this->model->find((int) $id);

        if (!$score) {
            $this->json(['error' => 'Score not found'], 404);
            return;
        }

        $raw = $this->input();
        $data = array_merge($score, $raw);
        $this->model->update((int) $id, $data);
        $updated = $this->model->find((int) $id);

        $this->json(['success' => true, 'message' => 'Score updated', 'data' => $updated]);
    }

    public function destroy(string $id): void
    {
        $this->requireRole(['admin', 'manager', 'composer', 'arranger']);

        $score = $this->model->find((int) $id);

        if (!$score) {
            $this->json(['error' => 'Score not found'], 404);
            return;
        }

        $this->model->delete((int) $id);

        $this->json(['success' => true, 'message' => 'Score deleted']);
    }

    public function uploadPdf(string $id): void
    {
        $this->requireRole(['admin', 'manager', 'composer', 'arranger']);

        $score = $this->model->find((int) $id);

        if (!$score) {
            $this->json(['error' => 'Score not found'], 404);
            return;
        }

        if (empty($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
            $errCode = $_FILES['pdf']['error'] ?? -1;
            $this->json(['error' => 'No file uploaded or upload error: ' . $this->uploadErrorMessage($errCode)], 422);
            return;
        }

        $file = $_FILES['pdf'];

        // Validate MIME type
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if ($mime !== 'application/pdf') {
            $this->json(['error' => 'Only PDF files are allowed.'], 422);
            return;
        }

        // Validate file size (max 20 MB)
        $maxSize = 20 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            $this->json(['error' => 'File size exceeds the 20 MB limit.'], 422);
            return;
        }

        // Create upload directory if it doesn't exist
        $uploadDir = __DIR__ . '/../../../../public/uploads/scores/pdf/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $filename = 'score_' . $id . '_' . uniqid('', true) . '.pdf';
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $this->json(['error' => 'Failed to save the uploaded file.'], 500);
            return;
        }

        // Build public URL
        $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost:8000', '/');
        $publicUrl = $appUrl . '/uploads/scores/pdf/' . $filename;

        // Update the score's file_url
        $this->model->update((int) $id, ['file_url' => $publicUrl]);
        $updated = $this->model->find((int) $id);

        $this->json(['success' => true, 'message' => 'PDF uploaded', 'data' => $updated]);
    }

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File too large.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension.',
            default => 'Unknown upload error.',
        };
    }

    private ?array $cachedUser = null;

    private function getAuthUser(): array
    {
        if ($this->cachedUser !== null) {
            return $this->cachedUser;
        }
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = '';
        if (str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
        }
        if (!$token) {
            $token = $_GET['token'] ?? '';
        }
        if (!$token) {
            $this->json(['error' => 'Unauthorized'], 401);
            exit;
        }
        $stmt = \App\Core\Database::getInstance()->prepare(
            'SELECT id, role FROM users WHERE api_token = :token AND (api_token_exp IS NULL OR api_token_exp > NOW())'
        );
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();
        if (!$user) {
            $this->json(['error' => 'Unauthorized'], 401);
            exit;
        }
        $this->cachedUser = $user;
        return $user;
    }

    private function requireRole(array $allowedRoles): void
    {
        $user = $this->getAuthUser();
        $userRole = strtolower($user['role'] ?? '');
        if (!in_array($userRole, $allowedRoles, true)) {
            $this->json(['error' => 'You do not have permission to perform this action'], 403);
            exit;
        }
    }

    private function getUserId(): int
    {
        return (int) $this->getAuthUser()['id'];
    }

    private function input(): array
    {
        $body = file_get_contents('php://input');
        $data = $body ? json_decode($body, true) : null;
        return is_array($data) ? $data : $_POST;
    }

    private function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
