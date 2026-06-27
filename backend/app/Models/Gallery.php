<?php

namespace App\Models;

use App\Core\Model;

class Gallery extends Model
{
    protected string $table = 'gallery';

    public function findByProgram(string $programId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE program_id = :program_id ORDER BY uploaded_at DESC");
        $stmt->execute(['program_id' => $programId]);
        return $stmt->fetchAll();
    }
}