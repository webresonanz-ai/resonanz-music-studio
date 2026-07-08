<?php

namespace App\Http\Controllers\Trms;

use App\Core\Database;
use App\Models\SeatHold;

class SeatHoldController
{
    private SeatHold $model;

    public function __construct()
    {
        $this->model = new SeatHold();
    }

    /**
     * POST /api/trms/seat-holds
     * Body: { schedule_id, seat_number }
     * Auth required — resolved via Bearer token.
     */
    public function hold(): void
    {
        header('Content-Type: application/json');

        $userId = $this->getAuthUserId();
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Login diperlukan untuk memilih kursi.']);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $scheduleId = (int) ($body['schedule_id'] ?? 0);
        $seatNumber = trim($body['seat_number'] ?? '');

        if ($scheduleId <= 0 || $seatNumber === '') {
            http_response_code(422);
            echo json_encode(['error' => 'schedule_id and seat_number are required']);
            return;
        }

        $result = $this->model->hold($scheduleId, $seatNumber, $userId);

        if (!empty($result['error'])) {
            http_response_code(409);
            echo json_encode(['error' => $result['error']]);
            return;
        }

        echo json_encode([
            'ok'         => true,
            'seat_number' => $seatNumber,
            'expires_at' => $result['expires_at'] ?? null,
        ]);
    }

    /**
     * POST /api/trms/seat-holds/release
     * Body: { schedule_id, seat_number }
     * Auth required.
     */
    public function release(): void
    {
        header('Content-Type: application/json');

        $userId = $this->getAuthUserId();
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $scheduleId = (int) ($body['schedule_id'] ?? 0);
        $seatNumber = trim($body['seat_number'] ?? '');

        if ($scheduleId <= 0 || $seatNumber === '') {
            http_response_code(422);
            echo json_encode(['error' => 'schedule_id and seat_number are required']);
            return;
        }

        $this->model->release($scheduleId, $seatNumber, $userId);
        echo json_encode(['ok' => true]);
    }

    /**
     * GET /api/trms/seat-holds/{scheduleId}
     * Returns seats currently held by the authenticated user for this schedule.
     * Auth required.
     */
    public function myHolds(string $scheduleId): void
    {
        header('Content-Type: application/json');

        $userId = $this->getAuthUserId();
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $holds = $this->model->getUserHolds((int) $scheduleId, $userId);
        echo json_encode(['data' => $holds]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function getAuthUserId(): ?int
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token  = '';

        if (preg_match('/Bearer\s+(.+)$/i', $header, $m)) {
            $token = $m[1];
        }

        if ($token === '') return null;

        $stmt = Database::getInstance()->prepare(
            'SELECT id FROM users WHERE api_token = :t AND api_token_exp > NOW() LIMIT 1'
        );
        $stmt->execute(['t' => $token]);
        $row = $stmt->fetch();

        return $row ? (int) $row['id'] : null;
    }
}
