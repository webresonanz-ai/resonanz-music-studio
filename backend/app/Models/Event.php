<?php

namespace App\Models;

use App\Core\Model;

class Event extends Model
{
    protected string $table = 'events';

    public function findByProgram(string $programId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE program_id = :program_id ORDER BY event_date DESC");
        $stmt->execute(['program_id' => $programId]);
        return $stmt->fetchAll();
    }
}