<?php

namespace App\Http\Controllers\Bms;

use App\Models\ConcertRoster;
use App\Models\Member;
use App\Models\Schedule;
use App\Models\ScheduleAttendance;

class AttendanceController
{
    private Schedule $schedules;
    private ConcertRoster $roster;
    private ScheduleAttendance $attendance;
    private Member $members;

    public function __construct()
    {
        $this->schedules = new Schedule();
        $this->roster = new ConcertRoster();
        $this->attendance = new ScheduleAttendance();
        $this->members = new Member();
    }

    /** GET /api/bms/attendance/concerts */
    public function concerts(): void
    {
        $concerts = $this->schedules->findConcertsByProgram('bms');
        $today = date('Y-m-d');

        $upcoming = array_values(array_filter(
            $concerts,
            fn(array $c) => ($c['date'] ?? '') >= $today
        ));

        $this->json([
            'concerts' => $upcoming,
            'all_concerts' => $concerts,
        ]);
    }

    /** GET /api/bms/attendance/concerts/{id} */
    public function show(string $id): void
    {
        $concertId = (int) $id;
        $concert = $this->schedules->find($concertId);

        if (!$concert || ($concert['type'] ?? '') !== 'concert') {
            $this->json(['error' => 'Concert schedule not found'], 404);
            return;
        }

        if (!$this->schedules->belongsToProgram($concertId, 'bms')) {
            $this->json(['error' => 'Concert is not part of the BMS program'], 404);
            return;
        }

        $roster = $this->roster->findByConcert($concertId);
        $rehearsals = $this->schedules->findRehearsalsBeforeDate('bms', $concert['date']);

        $memberIds = array_map(fn(array $r) => (int) $r['member_id'], $roster);
        $scheduleIds = array_map(fn(array $r) => (int) $r['id'], $rehearsals);

        $records = $this->attendance->findForSchedules($scheduleIds, $memberIds);

        $attendanceMap = [];
        foreach ($records as $record) {
            $key = $record['schedule_id'] . '-' . $record['member_id'];
            $attendanceMap[$key] = $record['status'];
        }

        $this->json([
            'concert' => $concert,
            'roster' => $roster,
            'rehearsals' => $rehearsals,
            'attendance' => $attendanceMap,
            'available_members' => $this->members->findByProgram('bms'),
        ]);
    }

    /** POST /api/bms/attendance/roster */
    public function updateRoster(): void
    {
        $data = $this->input();
        $concertId = (int) ($data['concert_schedule_id'] ?? 0);
        $memberId = (int) ($data['member_id'] ?? 0);
        $action = $data['action'] ?? '';

        if ($concertId <= 0 || $memberId <= 0 || !in_array($action, ['add', 'remove'], true)) {
            $this->json(['error' => 'concert_schedule_id, member_id, and action (add|remove) are required'], 422);
            return;
        }

        $concert = $this->schedules->find($concertId);
        if (!$concert || ($concert['type'] ?? '') !== 'concert') {
            $this->json(['error' => 'Concert schedule not found'], 404);
            return;
        }

        if (!$this->schedules->belongsToProgram($concertId, 'bms')) {
            $this->json(['error' => 'Concert is not part of the BMS program'], 404);
            return;
        }

        $member = $this->members->find($memberId);
        if (!$member || ($member['program_id'] ?? '') !== 'bms') {
            $this->json(['error' => 'Member not found'], 404);
            return;
        }

        if ($action === 'add') {
            $this->roster->addMember($concertId, $memberId);
            $message = 'Singer added to concert roster';
        } else {
            $this->roster->removeMember($concertId, $memberId);
            $message = 'Singer removed from concert roster';
        }

        $this->json([
            'success' => true,
            'message' => $message,
            'roster' => $this->roster->findByConcert($concertId),
        ]);
    }

    /** POST /api/bms/attendance/record */
    public function record(): void
    {
        $data = $this->input();
        $scheduleId = (int) ($data['schedule_id'] ?? 0);
        $memberId = (int) ($data['member_id'] ?? 0);
        $status = $data['status'] ?? null;

        if ($scheduleId <= 0 || $memberId <= 0) {
            $this->json(['error' => 'schedule_id and member_id are required'], 422);
            return;
        }

        $schedule = $this->schedules->find($scheduleId);
        if (!$schedule || ($schedule['type'] ?? '') !== 'practice') {
            $this->json(['error' => 'Rehearsal schedule not found'], 404);
            return;
        }

        if (!$this->schedules->belongsToProgram($scheduleId, 'bms')) {
            $this->json(['error' => 'Schedule is not part of the BMS program'], 404);
            return;
        }

        $concertId = (int) ($data['concert_schedule_id'] ?? 0);
        if ($concertId > 0) {
            $concert = $this->schedules->find($concertId);
            if (!$concert || ($schedule['date'] ?? '') > ($concert['date'] ?? '')) {
                $this->json(['error' => 'Rehearsal must be on or before the concert date'], 422);
                return;
            }

            if (!$this->roster->exists($concertId, $memberId)) {
                $this->json(['error' => 'Member is not on the concert roster'], 422);
                return;
            }
        }

        if ($status === null || $status === '') {
            $this->attendance->deleteRecord($scheduleId, $memberId);
            $this->json(['success' => true, 'message' => 'Attendance cleared', 'status' => null]);
            return;
        }

        if (!$this->attendance->upsert($scheduleId, $memberId, $status)) {
            $this->json(['error' => 'Invalid attendance status'], 422);
            return;
        }

        $this->json([
            'success' => true,
            'message' => 'Attendance recorded',
            'status' => $status,
        ]);
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
