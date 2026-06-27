<?php

namespace App\Models;

use App\Core\Model;

class Achievement extends Model
{
    protected string $table = 'achievements';

    public function findByProgram(string $programId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE program_id = :program_id ORDER BY year DESC");
        $stmt->execute(['program_id' => $programId]);
        return $stmt->fetchAll();
    }
}