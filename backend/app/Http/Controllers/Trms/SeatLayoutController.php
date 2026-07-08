<?php

namespace App\Http\Controllers\Trms;

use App\Models\SeatLayout;

class SeatLayoutController
{
    private SeatLayout $model;

    public function __construct()
    {
        $this->model = new SeatLayout();
    }

    /**
     * GET /api/trms/seat-layouts/{key}
     * Returns the full layout JSON for a given layout key.
     */
    public function show(string $key): void
    {
        $layout = $this->model->findByKey($key);

        if (!$layout) {
            http_response_code(404);
            echo json_encode(['error' => 'Layout not found']);
            return;
        }

        // Build response in the same shape as concertLayouts.js entries
        $data = $layout['layout_data'];
        echo json_encode([
            'id'          => $data['id']          ?? $layout['layout_key'],
            'name'        => $data['name']         ?? $layout['name'],
            'venue'       => $data['venue']        ?? $layout['venue'],
            'description' => $data['description']  ?? $layout['description'],
            'totalSeats'  => $data['totalSeats']   ?? (int) $layout['total_seats'],
            'isCustom'    => true,
            'sections'    => $data['sections']     ?? [],
        ]);
    }

    /**
     * POST /api/trms/seat-layouts
     * Saves a custom layout built in the frontend builder.
     *
     * Expected body (JSON):
     * {
     *   "id":          "custom-1720123456789",
     *   "name":        "Main Hall",
     *   "venue":       "Aula Simfonia",
     *   "description": "Custom layout · 120 seats",
     *   "totalSeats":  120,
     *   "isCustom":    true,
     *   "sections":    [ ... ]
     * }
     */
    public function store(): void
    {
        $raw  = file_get_contents('php://input');
        $body = json_decode($raw, true);

        if (!$body || empty($body['id'])) {
            http_response_code(422);
            echo json_encode(['error' => 'Invalid layout data']);
            return;
        }

        $layoutKey  = trim($body['id']);
        $name       = trim($body['name']        ?? 'Custom Layout');
        $venue      = trim($body['venue']       ?? '');
        $description = trim($body['description'] ?? '');
        $totalSeats = (int) ($body['totalSeats'] ?? 0);

        if (empty($layoutKey)) {
            http_response_code(422);
            echo json_encode(['error' => 'Layout id is required']);
            return;
        }

        // Validate sections array exists and is not empty
        if (empty($body['sections']) || !is_array($body['sections'])) {
            http_response_code(422);
            echo json_encode(['error' => 'Layout must contain at least one section with seats']);
            return;
        }

        $dbId = $this->model->upsert([
            'layout_key'  => $layoutKey,
            'name'        => $name,
            'venue'       => $venue,
            'description' => $description,
            'total_seats' => $totalSeats,
            'layout_data' => json_encode($body, JSON_UNESCAPED_UNICODE),
        ]);

        http_response_code(201);
        echo json_encode([
            'id'         => $dbId,
            'layout_key' => $layoutKey,
            'message'    => 'Layout saved successfully',
        ]);
    }

    /**
     * GET /api/trms/seat-layouts
     * Returns list of all saved custom layouts (without full section data).
     */
    public function index(): void
    {
        $layouts = $this->model->allCustom();
        echo json_encode(['data' => $layouts]);
    }
}
