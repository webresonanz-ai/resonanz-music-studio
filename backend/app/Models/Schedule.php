<?php

namespace App\Models;

use App\Core\Model;

class Schedule extends Model
{
    protected string $table = 'schedules';

    public function findByProgram(string $programId): array
    {
        $stmt = $this->db->prepare("
            SELECT s.*, 
                   (SELECT GROUP_CONCAT(sp.program_id) FROM schedule_programs sp WHERE sp.schedule_id = s.id) as program_ids
            FROM {$this->table} s
            WHERE s.id IN (
                SELECT schedule_id FROM schedule_programs WHERE program_id = :program_id
            )
            ORDER BY s.date ASC, s.start_time ASC
        ");
        $stmt->execute(['program_id' => $programId]);
        $results = $stmt->fetchAll();
        foreach ($results as &$row) {
            $row['program_ids'] = $row['program_ids'] ? explode(',', $row['program_ids']) : [];
        }
        return $results;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT s.*, 
                   (SELECT GROUP_CONCAT(sp.program_id) FROM schedule_programs sp WHERE sp.schedule_id = s.id) as program_ids
            FROM {$this->table} s
            WHERE s.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch() ?: null;
        if ($result) {
            $result['program_ids'] = $result['program_ids'] ? explode(',', $result['program_ids']) : [];
        }
        return $result;
    }

    public function syncPrograms(int $scheduleId, array $programIds): void
    {
        $stmt = $this->db->prepare("DELETE FROM schedule_programs WHERE schedule_id = :schedule_id");
        $stmt->execute(['schedule_id' => $scheduleId]);

        if (!empty($programIds)) {
            $stmt = $this->db->prepare("INSERT INTO schedule_programs (schedule_id, program_id) VALUES (:schedule_id, :program_id)");
            foreach ($programIds as $programId) {
                $stmt->execute([
                    'schedule_id' => $scheduleId,
                    'program_id' => $programId
                ]);
            }
        }
    }
}
