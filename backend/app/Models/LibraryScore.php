<?php

namespace App\Models;

use App\Core\Model;

class LibraryScore extends Model
{
    protected string $table = 'library_scores';

    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY title ASC");
        return $stmt->fetchAll();
    }
}
