<?php

namespace App\Models;

use App\Core\Model;

class Schedule extends Model
{
    protected string $table = 'schedules';

    public function findByProgram(string $programId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE program_id = :program_id ORDER BY date ASC, start_time ASC");
        $stmt->execute(['program_id' => $programId]);
        return $stmt->fetchAll();
    }
}
