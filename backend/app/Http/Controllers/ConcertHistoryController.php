<?php

namespace App\Http\Controllers;

use App\Models\ConcertHistory;

class ConcertHistoryController
{
    private ConcertHistory $model;

    public function __construct()
    {
        $this->model = new ConcertHistory();
    }

    public function index(): void
    {
        header('Content-Type: application/json');
        $concerts = $this->model->all();
        usort($concerts, fn($a, $b) => strtotime($b['concert_date']) - strtotime($a['concert_date']));
        echo json_encode($concerts);
    }

    public function show(string $id): void
    {
        header('Content-Type: application/json');
        $concert = $this->model->find((int) $id);
        if (!$concert) {
            http_response_code(404);
            echo json_encode(['error' => 'Concert not found']);
            return;
        }
        echo json_encode($concert);
    }

    public function store(): void
    {
        header('Content-Type: application/json');
        $data = $_POST;

        if (empty($data['title']) || empty($data['concert_date'])) {
            http_response_code(422);
            echo json_encode(['error' => 'Title and concert date are required']);
            return;
        }

        $concert = [
            'title'            => trim($data['title']),
            'description'      => trim($data['description'] ?? ''),
            'concert_date'     => $data['concert_date'],
            'banner'           => trim($data['banner'] ?? ''),
            'youtube_link'     => trim($data['youtube_link'] ?? ''),
            'spotify_link'     => trim($data['spotify_link'] ?? ''),
            'apple_music_link' => trim($data['apple_music_link'] ?? ''),
        ];

        $id = $this->model->create($concert);
        $concert['id'] = $id;

        http_response_code(201);
        echo json_encode(['data' => $concert, 'message' => 'Concert created successfully']);
    }

    public function update(string $id): void
    {
        header('Content-Type: application/json');
        $existing = $this->model->find((int) $id);
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['error' => 'Concert not found']);
            return;
        }

        $data = $_POST;
        $updateData = [];

        if (isset($data['title']))
            $updateData['title'] = trim($data['title']);
        if (isset($data['description']))
            $updateData['description'] = trim($data['description']);
        if (isset($data['concert_date']))
            $updateData['concert_date'] = $data['concert_date'];
        if (array_key_exists('banner', $data))
            $updateData['banner'] = trim($data['banner'] ?? '');
        if (array_key_exists('youtube_link', $data))
            $updateData['youtube_link'] = trim($data['youtube_link'] ?? '');
        if (array_key_exists('spotify_link', $data))
            $updateData['spotify_link'] = trim($data['spotify_link'] ?? '');
        if (array_key_exists('apple_music_link', $data))
            $updateData['apple_music_link'] = trim($data['apple_music_link'] ?? '');

        $this->model->update((int) $id, $updateData);

        $updated = $this->model->find((int) $id);
        echo json_encode(['data' => $updated, 'message' => 'Concert updated successfully']);
    }

    public function destroy(string $id): void
    {
        header('Content-Type: application/json');
        $concert = $this->model->find((int) $id);
        if (!$concert) {
            http_response_code(404);
            echo json_encode(['error' => 'Concert not found']);
            return;
        }

        $this->model->delete((int) $id);
        echo json_encode(['success' => true, 'message' => 'Concert deleted successfully']);
    }

    public function uploadBanner(): void
    {
        header('Content-Type: application/json');

        $uploadDir = __DIR__ . '/../../../public/uploads/concert-history/';
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $maxSize = 3 * 1024 * 1024;

        if (empty($_FILES['banner']) || $_FILES['banner']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(422);
            echo json_encode(['error' => 'No file uploaded or upload error']);
            return;
        }

        $file = $_FILES['banner'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (!in_array($mime, $allowedTypes, true)) {
            http_response_code(422);
            echo json_encode(['error' => 'Only JPEG, PNG, and WebP images are allowed']);
            return;
        }

        if ($file['size'] > $maxSize) {
            http_response_code(422);
            echo json_encode(['error' => 'File size exceeds the 3 MB limit']);
            return;
        }

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = match ($mime) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
        $filename = 'concert_' . uniqid('', true) . '.' . $ext;
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save the uploaded file']);
            return;
        }

        $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost:8000', '/');
        $publicUrl = $appUrl . '/uploads/concert-history/' . $filename;

        echo json_encode(['url' => $publicUrl]);
    }
}
