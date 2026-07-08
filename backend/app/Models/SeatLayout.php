<?php

namespace App\Models;

use App\Core\Model;

class SeatLayout extends Model
{
    protected string $table = 'seat_layouts';

    /**
     * Find a layout by its string key (e.g. "custom-1720123456789").
     */
    public function findByKey(string $key): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE layout_key = :key LIMIT 1"
        );
        $stmt->execute(['key' => $key]);
        $row = $stmt->fetch();
        if (!$row) return null;

        // Decode the stored JSON back to an array
        $row['layout_data'] = json_decode($row['layout_data'], true);
        return $row;
    }

    /**
     * Insert or replace a custom layout by key.
     * Returns the database row id.
     */
    public function upsert(array $data): int
    {
        // Check if key already exists
        $stmt = $this->db->prepare(
            "SELECT id FROM {$this->table} WHERE layout_key = :key LIMIT 1"
        );
        $stmt->execute(['key' => $data['layout_key']]);
        $existing = $stmt->fetch();

        if ($existing) {
            $this->update((int) $existing['id'], $data);
            return (int) $existing['id'];
        }

        return $this->create($data);
    }

    /**
     * Return all custom layouts ordered by newest first.
     */
    public function allCustom(): array
    {
        $stmt = $this->db->query(
            "SELECT id, layout_key, name, venue, description, total_seats, created_at
             FROM {$this->table}
             ORDER BY created_at DESC"
        );
        return $stmt->fetchAll();
    }
}
