<?php

namespace App\Http\Controllers\Trms;

use App\Models\ConcertAudience;

class ConcertAudienceController
{
    private ConcertAudience $model;

    public function __construct()
    {
        $this->model = new ConcertAudience();
    }

    public function index(): void
    {
        header('Content-Type: application/json');

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 10)));

        $result = $this->model->paginate($perPage, $page);

        echo json_encode([
            'data' => $result['items'],
            'total' => $result['total'],
            'per_page' => $result['per_page'],
            'current_page' => $result['current_page'],
            'last_page' => $result['last_page'],
        ]);
    }

    public function store(): void
    {
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        $required = ['name', 'email', 'phone', 'concert_title', 'ticket_quantity'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(422);
                echo json_encode(['error' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
                return;
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode(['error' => 'A valid email address is required']);
            return;
        }

        try {
            $id = $this->model->create([
                'program_id' => 'trms',
                'name' => trim($data['name']),
                'email' => trim($data['email']),
                'phone' => trim($data['phone']),
                'concert_title' => trim($data['concert_title']),
                'ticket_quantity' => 1,
                'notes' => trim($data['notes'] ?? 'Guest')
            ]);
        } catch (\Throwable $error) {
            http_response_code(500);
            echo json_encode(['error' => 'Unable to save registration. Please check the database setup.']);
            return;
        }

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Registration submitted successfully',
            'id' => $id
        ]);
    }
}
