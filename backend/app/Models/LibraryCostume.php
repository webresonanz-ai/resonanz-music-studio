<?php

namespace App\Models;

use App\Core\Model;

class LibraryCostume extends Model
{
    protected string $table = 'library_costumes';

    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY name ASC");
        return $stmt->fetchAll();
    }
}
