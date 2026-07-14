<?php

namespace App\Http\Controllers\Library;

use App\Models\LibraryCostume;

class CostumeController
{
    private LibraryCostume $model;

    public function __construct()
    {
        $this->model = new LibraryCostume();
    }

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 20)));
        $search = $_GET['search'] ?? '';
        $groupCategory = $_GET['group_category'] ?? '';
        $type = $_GET['type'] ?? '';

        $result = $this->model->paginated($page, $perPage, $search, $groupCategory, $type);
        $this->json($result);
    }

    public function show(string $id): void
    {
        $costume = $this->model->find((int) $id);

        if (!$costume) {
            $this->json(['error' => 'Costume not found'], 404);
            return;
        }

        $this->json($costume);
    }

    public function store(): void
    {
        $this->requireRole();

        $data = $this->input();

        if (empty($data['name'])) {
            $this->json(['error' => 'Name is required'], 422);
            return;
        }

        $id = $this->model->create($data);
        $costume = $this->model->find($id);

        $this->json(['success' => true, 'message' => 'Costume created', 'data' => $costume], 201);
    }

    public function update(string $id): void
    {
        $this->requireRole();

        $costume = $this->model->find((int) $id);

        if (!$costume) {
            $this->json(['error' => 'Costume not found'], 404);
            return;
        }

        $raw = $this->input();
        $data = array_merge($costume, $raw);
        $this->model->update((int) $id, $data);
        $updated = $this->model->find((int) $id);

        $this->json(['success' => true, 'message' => 'Costume updated', 'data' => $updated]);
    }

    public function destroy(string $id): void
    {
        $this->requireRole();

        $costume = $this->model->find((int) $id);

        if (!$costume) {
            $this->json(['error' => 'Costume not found'], 404);
            return;
        }

        $this->model->delete((int) $id);

        $this->json(['success' => true, 'message' => 'Costume deleted']);
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

    private function requireRole(): void
    {
        $user = $this->getAuthUser();
        $userRole = strtolower($user['role'] ?? '');
        if (!in_array($userRole, ['admin', 'manager'], true)) {
            $this->json(['error' => 'You do not have permission to perform this action'], 403);
            exit;
        }
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
