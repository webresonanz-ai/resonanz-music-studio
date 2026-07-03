<?php

namespace App\Models;

use App\Core\Model;

class ConcertRoster extends Model
{
    protected string $table = 'concert_roster';

    public function findByConcert(int $concertScheduleId): array
    {
        $stmt = $this->db->prepare("
            SELECT cr.id AS roster_id, cr.concert_schedule_id, cr.member_id, cr.created_at,
                   m.name, m.nickname, m.stage_name, m.role, m.section, m.status, m.avatar
            FROM {$this->table} cr
            JOIN members m ON m.id = cr.member_id
            WHERE cr.concert_schedule_id = :concert_schedule_id
            ORDER BY m.name ASC
        ");
        $stmt->execute(['concert_schedule_id' => $concertScheduleId]);
        return $stmt->fetchAll();
    }

    public function exists(int $concertScheduleId, int $memberId): bool
    {
        $stmt = $this->db->prepare("
            SELECT id FROM {$this->table}
            WHERE concert_schedule_id = :concert_schedule_id AND member_id = :member_id
            LIMIT 1
        ");
        $stmt->execute([
            'concert_schedule_id' => $concertScheduleId,
            'member_id' => $memberId,
        ]);
        return (bool) $stmt->fetch();
    }

    public function addMember(int $concertScheduleId, int $memberId): int
    {
        if ($this->exists($concertScheduleId, $memberId)) {
            return 0;
        }

        return $this->create([
            'concert_schedule_id' => $concertScheduleId,
            'member_id' => $memberId,
        ]);
    }

    public function removeMember(int $concertScheduleId, int $memberId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM {$this->table}
            WHERE concert_schedule_id = :concert_schedule_id AND member_id = :member_id
        ");
        return $stmt->execute([
            'concert_schedule_id' => $concertScheduleId,
            'member_id' => $memberId,
        ]);
    }
}
