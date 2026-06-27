<?php

namespace App\Models;

use App\Core\Model;

class Member extends Model
{
    protected string $table = 'members';

    public function findByProgram(string $programId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE program_id = :program_id AND status = 'active'");
        $stmt->execute(['program_id' => $programId]);
        return $stmt->fetchAll();
    }
}