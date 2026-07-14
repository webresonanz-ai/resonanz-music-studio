<?php

namespace App\Models;

use App\Core\Model;

class Order extends Model
{
    protected string $table = 'orders';

    public function items(int $orderId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM order_items WHERE order_id = :order_id ORDER BY id ASC");
        $stmt->execute(['order_id' => $orderId]);
        return $stmt->fetchAll();
    }

    public function createItem(array $data): int
    {
        $fields = array_keys($data);
        $placeholders = array_map(fn($f) => ":{$f}", $fields);
        $sql = sprintf(
            "INSERT INTO order_items (%s) VALUES (%s)",
            implode(', ', $fields),
            implode(', ', $placeholders)
        );
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function byUser(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function generateOrderNumber(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    }

    public function saveSnapToken(int $orderId, string $snapToken): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET snap_token = :snap_token WHERE id = :id");
        return $stmt->execute(['snap_token' => $snapToken, 'id' => $orderId]);
    }

    public function updatePayment(int $orderId, array $data): bool
    {
        $fields = [];
        $params = ['id' => $orderId];
        foreach (['transaction_id', 'payment_type', 'snap_token'] as $key) {
            if (array_key_exists($key, $data)) {
                $fields[] = "{$key} = :{$key}";
                $params[$key] = $data[$key];
            }
        }
        if (empty($fields)) return true;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        return $this->db->prepare($sql)->execute($params);
    }
}
