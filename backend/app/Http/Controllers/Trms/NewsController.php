<?php

namespace App\Http\Controllers\Trms;

use App\Models\News;

class NewsController
{
    private News $model;

    public function __construct()
    {
        $this->model = new News();
    }

    public function index(): void
    {
        $programId = $_GET['program_id'] ?? null;
        if ($programId) {
            $articles = $this->model->findByProgram($programId);
        } else {
            $articles = $this->model->all();
        }

        header('Content-Type: application/json');
        echo json_encode($articles);
    }

    public function store(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);

        $title = trim($input['title'] ?? '');
        $content = trim($input['content'] ?? '');
        $programIds = $input['program_ids'] ?? ['trms'];
        $publishedAt = $input['published_at'] ?? date('Y-m-d');

        if (!$title) {
            http_response_code(422);
            echo json_encode(['error' => 'Title is required.']);
            return;
        }

        $id = $this->model->create([
            'title' => $title,
            'content' => $content,
            'published_at' => $publishedAt,
        ]);

        $this->model->syncPrograms($id, $programIds);
        $article = $this->model->find($id);

        header('Content-Type: application/json');
        http_response_code(201);
        echo json_encode(['data' => $article]);
    }

    public function update(int $id): void
    {
        $existing = $this->model->find($id);
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['error' => 'News article not found.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $data = [];
        if (isset($input['title'])) $data['title'] = trim($input['title']);
        if (isset($input['content'])) $data['content'] = trim($input['content']);
        if (isset($input['published_at'])) $data['published_at'] = $input['published_at'];

        if (empty($data['title'] ?? $existing['title'])) {
            http_response_code(422);
            echo json_encode(['error' => 'Title is required.']);
            return;
        }

        if (!empty($data)) {
            $this->model->update($id, $data);
        }

        if (isset($input['program_ids'])) {
            $this->model->syncPrograms($id, $input['program_ids']);
        }

        $article = $this->model->find($id);

        header('Content-Type: application/json');
        echo json_encode(['data' => $article]);
    }

    public function destroy(int $id): void
    {
        $existing = $this->model->find($id);
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['error' => 'News article not found.']);
            return;
        }

        $this->model->delete($id);

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }
}
