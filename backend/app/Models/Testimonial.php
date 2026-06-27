<?php

namespace App\Models;

use App\Core\Model;

class Testimonial extends Model
{
    protected string $table = 'testimonials';

    public function findByProgram(string $programId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE program_id = :program_id");
        $stmt->execute(['program_id' => $programId]);
        return $stmt->fetchAll();
    }
}