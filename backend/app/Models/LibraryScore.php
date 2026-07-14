<?php

namespace App\Models;

use App\Core\Model;

class LibraryScore extends Model
{
    protected string $table = 'library_scores';

    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY title ASC");
        $scores = $stmt->fetchAll();
        foreach ($scores as &$s) {
            $s['price'] = (float) ($s['price'] ?? 0);
        }
        return $scores;
    }

    public function find(int $id): ?array
    {
        $result = parent::find($id);
        if ($result) {
            $result['price'] = (float) ($result['price'] ?? 0);
        }
        return $result;
    }
}
