<?php

namespace App\Http\Controllers\Bms;

use App\Models\Schedule;

class ScheduleController
{
    private Schedule $model;

    public function __construct()
    {
        $this->model = new Schedule();
    }

    public function store(): void
    {
        header('Content-Type: application/json');

        $data = $_POST;

        if (empty($data['title']) || empty($data['date']) || empty($data['start_time']) || empty($data['end_time'])) {
            http_response_code(422);
            echo json_encode(['error' => 'Title, date, start time, and end time are required']);
            return;
        }

        $type = $data['type'] ?? 'practice';

        $schedule = [
            'program_id'       => 'bms',
            'title'            => trim($data['title']),
            'type'             => $type,
            'date'             => $data['date'],
            'start_time'       => $data['start_time'],
            'end_time'         => $data['end_time'],
            'venue'            => trim($data['venue'] ?? ''),
            'concert_code'     => '',
            'description'      => $data['description'] ?? '',
            'banner_url'       => '',
            'is_open_register' => 0,
            'is_redirect_url'  => 0,
            'redirect_url'     => null,
            'audience_capacity'=> null,
            'is_seat_assign'   => 0,
            'seat_layout_id'   => null,
        ];

        $id = $this->model->create($schedule);
        $this->model->syncPrograms((int) $id, ['bms']);

        http_response_code(201);
        echo json_encode(['id' => $id, 'message' => 'Schedule created successfully']);
    }

    public function update(string $id): void
    {
        header('Content-Type: application/json');

        $schedule = $this->model->find((int) $id);
        if (!$schedule) {
            http_response_code(404);
            echo json_encode(['error' => 'Schedule not found']);
            return;
        }

        if (!$this->model->belongsToProgram((int) $id, 'bms')) {
            http_response_code(403);
            echo json_encode(['error' => 'Schedule does not belong to BMS']);
            return;
        }

        $data = $_POST;
        $updateData = [];

        if (isset($data['title']))
            $updateData['title'] = trim($data['title']);
        if (isset($data['type']))
            $updateData['type'] = $data['type'];
        if (isset($data['date']))
            $updateData['date'] = $data['date'];
        if (isset($data['start_time']))
            $updateData['start_time'] = $data['start_time'];
        if (isset($data['end_time']))
            $updateData['end_time'] = $data['end_time'];
        if (array_key_exists('venue', $data))
            $updateData['venue'] = trim($data['venue'] ?? '');
        if (isset($data['description']))
            $updateData['description'] = trim($data['description']);

        $this->model->update((int) $id, $updateData);

        if (isset($data['program_ids'])) {
            $this->model->syncPrograms((int) $id, $data['program_ids']);
        }

        echo json_encode(['message' => 'Schedule updated successfully']);
    }

    public function destroy(string $id): void
    {
        header('Content-Type: application/json');

        $schedule = $this->model->find((int) $id);
        if (!$schedule) {
            http_response_code(404);
            echo json_encode(['error' => 'Schedule not found']);
            return;
        }

        if (!$this->model->belongsToProgram((int) $id, 'bms')) {
            http_response_code(403);
            echo json_encode(['error' => 'Schedule does not belong to BMS']);
            return;
        }

        $this->model->delete((int) $id);
        echo json_encode(['message' => 'Schedule deleted successfully']);
    }
}
