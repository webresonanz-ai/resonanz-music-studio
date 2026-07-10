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
        $this->json($this->model->all());
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
        $costume = $this->model->find((int) $id);

        if (!$costume) {
            $this->json(['error' => 'Costume not found'], 404);
            return;
        }

        $this->model->delete((int) $id);

        $this->json(['success' => true, 'message' => 'Costume deleted']);
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
