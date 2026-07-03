<?php

namespace App\Models;

use App\Core\Model;

/**
 * Manages the explicit mapping between a BMS concert schedule and
 * the practice schedules (rehearsals) that belong to it.
 */
class ConcertRehearsal extends Model
{
    protected string $table = 'concert_rehearsals';

    // ── Read ──────────────────────────────────────────────────────────────

    /**
     * Return all rehearsal schedule rows linked to a concert, ordered by date.
     */
    public function findByConcert(int $concertScheduleId): array
    {
        $stmt = $this->db->prepare("
            SELECT s.id, s.title, s.date, s.start_time, s.end_time, s.description,
                   cr.sort_order
            FROM {$this->table} cr
            JOIN schedules s ON s.id = cr.rehearsal_id
            WHERE cr.concert_schedule_id = :concert_id
            ORDER BY s.date ASC, s.start_time ASC
        ");
        $stmt->execute(['concert_id' => $concertScheduleId]);
        return $stmt->fetchAll();
    }

    /**
     * Return the set of rehearsal_ids linked to a concert.
     */
    public function getRehearsalIds(int $concertScheduleId): array
    {
        $stmt = $this->db->prepare("
            SELECT rehearsal_id FROM {$this->table}
            WHERE concert_schedule_id = :concert_id
        ");
        $stmt->execute(['concert_id' => $concertScheduleId]);
        return array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'rehearsal_id');
    }

    // ── Write ─────────────────────────────────────────────────────────────

    /**
     * Link a rehearsal schedule to a concert. Idempotent.
     */
    public function link(int $concertScheduleId, int $rehearsalId): void
    {
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO {$this->table} (concert_schedule_id, rehearsal_id)
            VALUES (:concert_id, :rehearsal_id)
        ");
        $stmt->execute([
            'concert_id'   => $concertScheduleId,
            'rehearsal_id' => $rehearsalId,
        ]);
    }

    /**
     * Unlink a rehearsal schedule from a concert.
     */
    public function unlink(int $concertScheduleId, int $rehearsalId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM {$this->table}
            WHERE concert_schedule_id = :concert_id AND rehearsal_id = :rehearsal_id
        ");
        return $stmt->execute([
            'concert_id'   => $concertScheduleId,
            'rehearsal_id' => $rehearsalId,
        ]);
    }

    /**
     * Replace the entire rehearsal list for a concert.
     * Accepts an ordered array of rehearsal_ids.
     */
    public function syncRehearsals(int $concertScheduleId, array $rehearsalIds): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table} WHERE concert_schedule_id = :concert_id"
        );
        $stmt->execute(['concert_id' => $concertScheduleId]);

        if (empty($rehearsalIds)) {
            return;
        }

        $insert = $this->db->prepare("
            INSERT INTO {$this->table} (concert_schedule_id, rehearsal_id, sort_order)
            VALUES (:concert_id, :rehearsal_id, :sort_order)
        ");
        foreach ($rehearsalIds as $order => $id) {
            $insert->execute([
                'concert_id'   => $concertScheduleId,
                'rehearsal_id' => (int) $id,
                'sort_order'   => $order,
            ]);
        }
    }

    /**
     * Count rehearsals linked to a concert.
     */
    public function countByConcert(int $concertScheduleId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE concert_schedule_id = :concert_id"
        );
        $stmt->execute(['concert_id' => $concertScheduleId]);
        return (int) $stmt->fetchColumn();
    }
}
