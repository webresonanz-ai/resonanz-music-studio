<?php

namespace App\Http\Controllers\Bms;

use App\Models\ConcertRehearsal;
use App\Models\ConcertRoster;
use App\Models\Member;
use App\Models\Schedule;
use App\Models\ScheduleAttendance;

class AttendanceController
{
    private Schedule           $schedules;
    private ConcertRoster      $roster;
    private ConcertRehearsal   $rehearsals;
    private ScheduleAttendance $attendance;
    private Member             $members;

    public function __construct()
    {
        $this->schedules  = new Schedule();
        $this->roster     = new ConcertRoster();
        $this->rehearsals = new ConcertRehearsal();
        $this->attendance = new ScheduleAttendance();
        $this->members    = new Member();
    }

    // ══════════════════════════════════════════════════════════════════════
    // GET /api/bms/attendance/concerts
    // Returns upcoming + all BMS concert schedules.
    // ══════════════════════════════════════════════════════════════════════
    public function concerts(): void
    {
        $concerts = $this->schedules->findConcertsByProgram('bms');
        $today    = date('Y-m-d');

        // Attach rehearsal count to each concert
        foreach ($concerts as &$concert) {
            $concert['rehearsal_count'] = $this->rehearsals->countByConcert((int) $concert['id']);
        }
        unset($concert);

        $upcoming = array_values(array_filter(
            $concerts,
            fn(array $c) => ($c['date'] ?? '') >= $today
        ));

        $this->json([
            'concerts'     => $upcoming,
            'all_concerts' => $concerts,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // GET /api/bms/attendance/concerts/{id}
    // Full detail: concert info, roster, linked rehearsals, attendance map.
    // ══════════════════════════════════════════════════════════════════════
    public function show(string $id): void
    {
        $concertId = (int) $id;
        $concert   = $this->schedules->find($concertId);

        if (!$concert || ($concert['type'] ?? '') !== 'concert') {
            $this->json(['error' => 'Concert schedule not found'], 404);
            return;
        }

        if (!$this->schedules->belongsToProgram($concertId, 'bms')) {
            $this->json(['error' => 'Concert is not part of the BMS program'], 404);
            return;
        }

        $roster         = $this->roster->findByConcert($concertId);
        $rehearsalRows  = $this->rehearsals->findByConcert($concertId);

        // Also expose all available BMS practice schedules (before concert date)
        // so the admin can add/remove them.
        $availablePractices = $this->schedules->findRehearsalsBeforeDate('bms', $concert['date']);
        $linkedIds          = array_column($rehearsalRows, 'id');

        $memberIds    = array_map(fn(array $r) => (int) $r['member_id'], $roster);
        $scheduleIds  = array_column($rehearsalRows, 'id');

        $records = $this->attendance->findForSchedules($scheduleIds, $memberIds);

        $attendanceMap = [];
        foreach ($records as $record) {
            $key = $record['schedule_id'] . '-' . $record['member_id'];
            $attendanceMap[$key] = $record['status'];
        }

        $this->json([
            'concert'            => $concert,
            'roster'             => $roster,
            'rehearsals'         => $rehearsalRows,
            'available_practices'=> $availablePractices,
            'linked_rehearsal_ids'=> $linkedIds,
            'attendance'         => $attendanceMap,
            'available_members'  => $this->members->findByProgram('bms'),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // GET /api/bms/attendance/concerts/{id}/by-date/{date}
    // Returns singer list + existing present-status for one rehearsal date.
    // ══════════════════════════════════════════════════════════════════════
    public function byDate(string $id, string $date): void
    {
        $concertId = (int) $id;
        $concert   = $this->schedules->find($concertId);

        if (!$concert || ($concert['type'] ?? '') !== 'concert') {
            $this->json(['error' => 'Concert schedule not found'], 404);
            return;
        }

        if (!$this->schedules->belongsToProgram($concertId, 'bms')) {
            $this->json(['error' => 'Concert is not part of the BMS program'], 404);
            return;
        }

        // Find the rehearsal schedule(s) on this date that belong to this concert
        $rehearsalRows = $this->rehearsals->findByConcert($concertId);
        $onDate = array_values(array_filter($rehearsalRows, fn($r) => $r['date'] === $date));

        if (empty($onDate)) {
            $this->json(['error' => 'No linked rehearsal found for this date'], 404);
            return;
        }

        $rehearsal  = $onDate[0];
        $scheduleId = (int) $rehearsal['id'];

        $roster    = $this->roster->findByConcert($concertId);
        $memberIds = array_map(fn(array $r) => (int) $r['member_id'], $roster);

        $records = $this->attendance->findForSchedules([$scheduleId], $memberIds);

        // Build a simple memberId → status map
        $present = [];
        foreach ($records as $rec) {
            $present[(int) $rec['member_id']] = $rec['status'];
        }

        // Attach status to each roster member
        foreach ($roster as &$singer) {
            $singer['attendance_status'] = $present[(int) $singer['member_id']] ?? null;
        }
        unset($singer);

        $this->json([
            'concert'   => ['id' => $concert['id'], 'title' => $concert['title'], 'date' => $concert['date']],
            'rehearsal' => $rehearsal,
            'singers'   => $roster,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // POST /api/bms/attendance/roster  (protected)
    // Add or remove a singer from a concert roster.
    // ══════════════════════════════════════════════════════════════════════
    public function updateRoster(): void
    {
        $data      = $this->input();
        $concertId = (int) ($data['concert_schedule_id'] ?? 0);
        $memberId  = (int) ($data['member_id'] ?? 0);
        $action    = $data['action'] ?? '';

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
            'roster'  => $this->roster->findByConcert($concertId),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // POST /api/bms/attendance/rehearsals  (protected)
    // Link or unlink a practice schedule to/from a concert.
    // Body: { concert_schedule_id, rehearsal_id, action: 'link'|'unlink' }
    //   OR sync: { concert_schedule_id, rehearsal_ids: [1,2,3] }
    // ══════════════════════════════════════════════════════════════════════
    public function updateRehearsals(): void
    {
        $data      = $this->input();
        $concertId = (int) ($data['concert_schedule_id'] ?? 0);

        if ($concertId <= 0) {
            $this->json(['error' => 'concert_schedule_id is required'], 422);
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

        // Sync mode: replace entire rehearsal list
        if (isset($data['rehearsal_ids']) && is_array($data['rehearsal_ids'])) {
            // Validate all IDs are valid BMS practice schedules before concert date
            $validIds = [];
            foreach ($data['rehearsal_ids'] as $rid) {
                $rid  = (int) $rid;
                $rsch = $this->schedules->find($rid);
                if (
                    $rsch &&
                    ($rsch['type'] ?? '') === 'practice' &&
                    ($rsch['date'] ?? '') < ($concert['date'] ?? '') &&
                    $this->schedules->belongsToProgram($rid, 'bms')
                ) {
                    $validIds[] = $rid;
                }
            }
            $this->rehearsals->syncRehearsals($concertId, $validIds);
            $this->json([
                'success'    => true,
                'message'    => 'Rehearsal list updated',
                'rehearsals' => $this->rehearsals->findByConcert($concertId),
            ]);
            return;
        }

        // Single link/unlink mode
        $rehearsalId = (int) ($data['rehearsal_id'] ?? 0);
        $action      = $data['action'] ?? '';

        if ($rehearsalId <= 0 || !in_array($action, ['link', 'unlink'], true)) {
            $this->json(['error' => 'rehearsal_id and action (link|unlink) are required'], 422);
            return;
        }

        $rehearsal = $this->schedules->find($rehearsalId);
        if (!$rehearsal || ($rehearsal['type'] ?? '') !== 'practice') {
            $this->json(['error' => 'Practice schedule not found'], 404);
            return;
        }

        if (($rehearsal['date'] ?? '') >= ($concert['date'] ?? '')) {
            $this->json(['error' => 'Rehearsal date must be before the concert date'], 422);
            return;
        }

        if (!$this->schedules->belongsToProgram($rehearsalId, 'bms')) {
            $this->json(['error' => 'Rehearsal is not part of the BMS program'], 404);
            return;
        }

        if ($action === 'link') {
            $this->rehearsals->link($concertId, $rehearsalId);
            $message = 'Rehearsal linked to concert';
        } else {
            $this->rehearsals->unlink($concertId, $rehearsalId);
            $message = 'Rehearsal unlinked from concert';
        }

        $this->json([
            'success'    => true,
            'message'    => $message,
            'rehearsals' => $this->rehearsals->findByConcert($concertId),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // POST /api/bms/attendance/record  (protected)
    // Record / clear attendance for a singer on a rehearsal.
    // Body: { concert_schedule_id, schedule_id, member_id, status }
    //   status = 'present' | 'late' | 'absent' | 'excused' | null (clears)
    // ══════════════════════════════════════════════════════════════════════
    public function record(): void
    {
        $data       = $this->input();
        $scheduleId = (int) ($data['schedule_id'] ?? 0);
        $memberId   = (int) ($data['member_id'] ?? 0);
        $status     = $data['status'] ?? null;

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
            if (!$concert || ($schedule['date'] ?? '') >= ($concert['date'] ?? '')) {
                $this->json(['error' => 'Rehearsal must be before the concert date'], 422);
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
            'status'  => $status,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // POST /api/bms/attendance/record/bulk  (protected)
    // Batch record for one rehearsal date.
    // Body: { concert_schedule_id, schedule_id, present_member_ids: [1,2,3] }
    // Everyone on the roster NOT in present_member_ids is marked absent.
    // ══════════════════════════════════════════════════════════════════════
    public function recordBulk(): void
    {
        $data            = $this->input();
        $concertId       = (int) ($data['concert_schedule_id'] ?? 0);
        $scheduleId      = (int) ($data['schedule_id'] ?? 0);
        $presentIds      = array_map('intval', (array) ($data['present_member_ids'] ?? []));
        $markAbsent      = (bool) ($data['mark_absent'] ?? false);

        if ($concertId <= 0 || $scheduleId <= 0) {
            $this->json(['error' => 'concert_schedule_id and schedule_id are required'], 422);
            return;
        }

        $concert = $this->schedules->find($concertId);
        if (!$concert || ($concert['type'] ?? '') !== 'concert') {
            $this->json(['error' => 'Concert schedule not found'], 404);
            return;
        }

        $schedule = $this->schedules->find($scheduleId);
        if (!$schedule || ($schedule['type'] ?? '') !== 'practice') {
            $this->json(['error' => 'Rehearsal schedule not found'], 404);
            return;
        }

        if (($schedule['date'] ?? '') >= ($concert['date'] ?? '')) {
            $this->json(['error' => 'Rehearsal must be before the concert date'], 422);
            return;
        }

        $roster    = $this->roster->findByConcert($concertId);
        $rosterIds = array_map(fn($r) => (int) $r['member_id'], $roster);

        $saved = 0;
        foreach ($rosterIds as $memberId) {
            if (in_array($memberId, $presentIds, true)) {
                $this->attendance->upsert($scheduleId, $memberId, 'present');
                $saved++;
            } elseif ($markAbsent) {
                $this->attendance->upsert($scheduleId, $memberId, 'absent');
                $saved++;
            } else {
                // Only clear if explicitly not marking absent — leave unchanged
            }
        }

        $this->json([
            'success' => true,
            'message' => "Attendance saved for {$saved} singer(s)",
            'saved'   => $saved,
        ]);
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
