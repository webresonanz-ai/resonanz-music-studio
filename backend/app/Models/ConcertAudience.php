<?php

namespace App\Models;

use App\Core\Model;

class ConcertAudience extends Model
{
    protected string $table = 'concert_audiences';

    public function findAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public function paginate(int $perPage = 10, int $page = 1): array
    {
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll();

        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
        ];
    }

    /**
     * Persist the generated QR code string for a registration record.
     */
    public function updateQrCode(int $id, string $qrCode): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET qr_code = :qr_code WHERE id = :id"
        );
        return $stmt->execute(['qr_code' => $qrCode, 'id' => $id]);
    }

    /**
     * Build the unique QR code identifier string.
     *
     * Format: {firstWordOfConcert}_{id}_{timestamp}_{rand4}
     * Example: SOLI_42_1751234567_A3kZ
     */
    public static function buildQrCode(string $concertTitle, int $id): string
    {
        // First word of the concert title, uppercased, non-alphanumeric chars stripped
        $firstWord = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', explode(' ', trim($concertTitle))[0]));
        if ($firstWord === '') {
            $firstWord = 'CONCERT';
        }

        $timestamp = time();

        // 4-character alphanumeric random suffix (A-Z, 0-9)
        $chars  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $rand4  = '';
        for ($i = 0; $i < 4; $i++) {
            $rand4 .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return "{$firstWord}_{$id}_{$timestamp}_{$rand4}";
    }
}
