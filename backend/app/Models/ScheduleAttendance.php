<?php

namespace App\Models;

use App\Core\Model;

class ScheduleAttendance extends Model
{
    protected string $table = 'schedule_attendance';

    private const VALID_STATUSES = ['present', 'absent', 'late', 'excused'];

    public function findForSchedules(array $scheduleIds, array $memberIds): array
    {
        if (empty($scheduleIds) || empty($memberIds)) {
            return [];
        }

        $schedulePlaceholders = implode(',', array_fill(0, count($scheduleIds), '?'));
        $memberPlaceholders = implode(',', array_fill(0, count($memberIds), '?'));

        $sql = "
            SELECT schedule_id, member_id, status, recorded_at
            FROM {$this->table}
            WHERE schedule_id IN ({$schedulePlaceholders})
              AND member_id IN ({$memberPlaceholders})
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([...$scheduleIds, ...$memberIds]);
        return $stmt->fetchAll();
    }

    public function upsert(int $scheduleId, int $memberId, string $status): bool
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            return false;
        }

        $existing = $this->findRecord($scheduleId, $memberId);

        if ($existing) {
            $stmt = $this->db->prepare("
                UPDATE {$this->table}
                SET status = :status, recorded_at = CURRENT_TIMESTAMP
                WHERE schedule_id = :schedule_id AND member_id = :member_id
            ");
            return $stmt->execute([
                'status' => $status,
                'schedule_id' => $scheduleId,
                'member_id' => $memberId,
            ]);
        }

        $this->create([
            'schedule_id' => $scheduleId,
            'member_id' => $memberId,
            'status' => $status,
        ]);

        return true;
    }

    public function deleteRecord(int $scheduleId, int $memberId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM {$this->table}
            WHERE schedule_id = :schedule_id AND member_id = :member_id
        ");
        return $stmt->execute([
            'schedule_id' => $scheduleId,
            'member_id' => $memberId,
        ]);
    }

    private function findRecord(int $scheduleId, int $memberId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE schedule_id = :schedule_id AND member_id = :member_id
            LIMIT 1
        ");
        $stmt->execute([
            'schedule_id' => $scheduleId,
            'member_id' => $memberId,
        ]);
        return $stmt->fetch() ?: null;
    }
}
