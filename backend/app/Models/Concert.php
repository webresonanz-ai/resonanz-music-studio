<?php

namespace App\Models;

use App\Core\Model;

class Concert extends Model
{
    protected string $table = 'concerts';

    public function findAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY concert_date DESC");
        return $stmt->fetchAll();
    }
}