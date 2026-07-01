<?php

namespace App\Http\Controllers\Trms;

use App\Models\Schedule;

class ScheduleController
{
    private Schedule $model;

    public function __construct()
    {
        $this->model = new Schedule();
    }

    public function index(): void
    {
        header('Content-Type: application/json');
        echo json_encode($this->model->findByProgram('trms'));
    }

    public function store(): void
    {
        $data = $_POST;

        if (empty($data['title']) || empty($data['date']) || empty($data['start_time']) || empty($data['end_time'])) {
            http_response_code(422);
            echo json_encode(['error' => 'Title, date, start time, and end time are required']);
            return;
        }

        $schedule = [
            'program_id' => 'trms',
            'title' => trim($data['title']),
            'type' => $data['type'] ?? 'lesson',
            'date' => $data['date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'description' => $data['description'] ?? ''
        ];

        $id = $this->model->create($schedule);
        http_response_code(201);
        echo json_encode(['id' => $id, 'message' => 'Schedule created successfully']);
    }

    public function update(string $id): void
    {
        $schedule = $this->model->find((int)$id);
        if (!$schedule) {
            http_response_code(404);
            echo json_encode(['error' => 'Schedule not found']);
            return;
        }

        $data = $_POST;
        $updateData = [];

        if (isset($data['title'])) $updateData['title'] = trim($data['title']);
        if (isset($data['type'])) $updateData['type'] = $data['type'];
        if (isset($data['date'])) $updateData['date'] = $data['date'];
        if (isset($data['start_time'])) $updateData['start_time'] = $data['start_time'];
        if (isset($data['end_time'])) $updateData['end_time'] = $data['end_time'];
        if (isset($data['description'])) $updateData['description'] = trim($data['description']);

        $this->model->update((int)$id, $updateData);
        echo json_encode(['message' => 'Schedule updated successfully']);
    }

    public function destroy(string $id): void
    {
        $schedule = $this->model->find((int)$id);
        if (!$schedule) {
            http_response_code(404);
            echo json_encode(['error' => 'Schedule not found']);
            return;
        }

        $this->model->delete((int)$id);
        echo json_encode(['message' => 'Schedule deleted successfully']);
    }
}
