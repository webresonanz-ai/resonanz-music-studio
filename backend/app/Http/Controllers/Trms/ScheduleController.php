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

        $programIds = $data['program_ids'] ?? ['trms'];
        $primaryProgramId = !empty($programIds) ? $programIds[0] : 'trms';
        $type = $data['type'] ?? 'lesson';
        $concertCode = $this->normalizeConcertCode($data['concert_code'] ?? '');
        $isOpenRegister = !empty($data['is_open_register']) ? 1 : 0;
        $isRedirectUrl  = !empty($data['is_redirect_url']) ? 1 : 0;
        $redirectUrl    = ($isRedirectUrl && !empty($data['redirect_url'])) ? trim($data['redirect_url']) : null;

        if ($type === 'concert' && $isOpenRegister && !$isRedirectUrl && $concertCode === '') {
            http_response_code(422);
            echo json_encode(['error' => 'Concert Code is required before opening registration']);
            return;
        }

        $isSeatAssign  = !empty($data['is_seat_assign']) ? 1 : 0;
        $seatLayoutId  = $isSeatAssign ? trim($data['seat_layout_id'] ?? '') : null;

        $schedule = [
            'program_id'       => $primaryProgramId,
            'title'            => trim($data['title']),
            'type'             => $type,
            'date'             => $data['date'],
            'start_time'       => $data['start_time'],
            'end_time'         => $data['end_time'],
            'venue'            => trim($data['venue'] ?? ''),
            'concert_code'     => $concertCode,
            'description'      => $data['description'] ?? '',
            'banner_url'       => trim($data['banner_url'] ?? ''),
            'is_open_register' => $isOpenRegister,
            'is_redirect_url'  => $isRedirectUrl,
            'redirect_url'     => $redirectUrl,
            'audience_capacity'=> isset($data['audience_capacity']) && $data['audience_capacity'] !== '' ? (int) $data['audience_capacity'] : null,
            'is_seat_assign'   => $isSeatAssign,
            'seat_layout_id'   => $seatLayoutId ?: null,
        ];

        $id = $this->model->create($schedule);

        // Sync multiple program associations
        $this->model->syncPrograms($id, $programIds);

        http_response_code(201);
        echo json_encode(['id' => $id, 'message' => 'Schedule created successfully']);
    }

    public function update(string $id): void
    {
        $schedule = $this->model->find((int) $id);
        if (!$schedule) {
            http_response_code(404);
            echo json_encode(['error' => 'Schedule not found']);
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
        if (array_key_exists('concert_code', $data))
            $updateData['concert_code'] = $this->normalizeConcertCode($data['concert_code'] ?? '');
        if (isset($data['description']))
            $updateData['description'] = trim($data['description']);
        if (array_key_exists('banner_url', $data))
            $updateData['banner_url'] = trim($data['banner_url'] ?? '');
        if (array_key_exists('is_open_register', $data))
            $updateData['is_open_register'] = !empty($data['is_open_register']) ? 1 : 0;
        if (array_key_exists('is_redirect_url', $data)) {
            $isRedirectUrl = !empty($data['is_redirect_url']) ? 1 : 0;
            $updateData['is_redirect_url'] = $isRedirectUrl;
            if ($isRedirectUrl && !empty($data['redirect_url'])) {
                $updateData['redirect_url'] = trim($data['redirect_url']);
            } else {
                $updateData['redirect_url'] = null;
            }
        }
        if (array_key_exists('audience_capacity', $data))
            $updateData['audience_capacity'] = ($data['audience_capacity'] !== '' && $data['audience_capacity'] !== null) ? (int) $data['audience_capacity'] : null;
        if (array_key_exists('is_seat_assign', $data))
            $updateData['is_seat_assign'] = !empty($data['is_seat_assign']) ? 1 : 0;
        if (array_key_exists('seat_layout_id', $data))
            $updateData['seat_layout_id'] = trim($data['seat_layout_id'] ?? '') ?: null;

        $nextType = $updateData['type'] ?? ($schedule['type'] ?? 'lesson');
        $nextConcertCode = $updateData['concert_code'] ?? $this->normalizeConcertCode($schedule['concert_code'] ?? '');
        $nextIsOpenRegister = $updateData['is_open_register'] ?? (int) ($schedule['is_open_register'] ?? 0);
        $nextIsRedirectUrl = $updateData['is_redirect_url'] ?? (int) ($schedule['is_redirect_url'] ?? 0);

        if ($nextType === 'concert' && $nextIsOpenRegister && !$nextIsRedirectUrl && $nextConcertCode === '') {
            http_response_code(422);
            echo json_encode(['error' => 'Concert Code is required before opening registration']);
            return;
        }

        if (isset($data['program_ids'])) {
            $programIds = $data['program_ids'];
            if (!empty($programIds)) {
                $updateData['program_id'] = $programIds[0];
            }
        }

        $this->model->update((int) $id, $updateData);

        if (isset($data['program_ids'])) {
            $this->model->syncPrograms((int) $id, $data['program_ids']);
        }

        echo json_encode(['message' => 'Schedule updated successfully']);
    }

    public function destroy(string $id): void
    {
        $schedule = $this->model->find((int) $id);
        if (!$schedule) {
            http_response_code(404);
            echo json_encode(['error' => 'Schedule not found']);
            return;
        }

        $this->model->delete((int) $id);
        echo json_encode(['message' => 'Schedule deleted successfully']);
    }

    private function normalizeConcertCode(string $value): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', trim($value)));
    }
}
