<?php

namespace App\Http\Controllers\Bms;

use App\Models\Member;

class MemberController
{
    private Member $model;

    public function __construct()
    {
        $this->model = new Member();
    }

    // ── GET /api/bms/members ──────────────────────────────────────────────

    public function index(): void
    {
        $this->json($this->model->findByProgram('bms'));
    }

    // ── GET /api/bms/members/{id} ─────────────────────────────────────────

    public function show(string $id): void
    {
        $member = $this->model->find((int) $id);

        if (!$member) {
            $this->json(['error' => 'Member not found'], 404);
            return;
        }

        $this->json($member);
    }

    // ── POST /api/bms/members ─────────────────────────────────────────────

    public function store(): void
    {
        $raw = $this->input();

        // Name is required for create
        $error = $this->model->validate($raw, true);
        if ($error) {
            $this->json(['error' => $error], 422);
            return;
        }

        $data = $this->model->sanitize($raw);
        $data['program_id'] = 'bms';

        $id = $this->model->create($data);
        $member = $this->model->find($id);

        $this->json(['success' => true, 'message' => 'Member created', 'data' => $member], 201);
    }

    // ── POST /api/bms/members/{id} (update) ──────────────────────────────

    public function update(string $id): void
    {
        $member = $this->model->find((int) $id);

        if (!$member) {
            $this->json(['error' => 'Member not found'], 404);
            return;
        }

        $raw = $this->input();

        // Name not strictly required for partial updates — only validate if provided
        $requireName = isset($raw['name']);
        $error = $this->model->validate($raw, $requireName);
        if ($error) {
            $this->json(['error' => $error], 422);
            return;
        }

        // Merge: only update fields that were actually sent
        $sanitized = $this->model->sanitize(array_merge($member, $raw));
        unset($sanitized['program_id']); // never update program_id via this endpoint

        $this->model->update((int) $id, $sanitized);
        $updated = $this->model->find((int) $id);

        $this->json(['success' => true, 'message' => 'Member updated', 'data' => $updated]);
    }

    // ── DELETE /api/bms/members/{id} ─────────────────────────────────────

    public function destroy(string $id): void
    {
        $member = $this->model->find((int) $id);

        if (!$member) {
            $this->json(['error' => 'Member not found'], 404);
            return;
        }

        $this->model->delete((int) $id);

        $this->json(['success' => true, 'message' => 'Member deleted']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

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
