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

    public function allWithOrderCount(): array
    {
        $sql = "SELECT s.*, COALESCE(paid_orders.cnt, 0) AS order_count
                FROM library_scores s
                LEFT JOIN (
                    SELECT oi.score_id, COUNT(DISTINCT oi.order_id) AS cnt
                    FROM order_items oi
                    JOIN orders o ON o.id = oi.order_id AND o.status = 'paid'
                    GROUP BY oi.score_id
                ) paid_orders ON paid_orders.score_id = s.id
                ORDER BY s.title ASC";
        $stmt = $this->db->query($sql);
        $scores = $stmt->fetchAll();
        foreach ($scores as &$s) {
            $s['price'] = (float) ($s['price'] ?? 0);
            $s['order_count'] = (int) ($s['order_count'] ?? 0);
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
