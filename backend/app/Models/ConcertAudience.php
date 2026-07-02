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

    public function count(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        return (int) $stmt->fetchColumn();
    }

public function paginate(int $perPage = 10, int $page = 1, string $search = ''): array
     {
         $offset = ($page - 1) * $perPage;

         $whereClause = '';
         $params = [];

          if ($search !== '') {
              $whereClause = "WHERE name LIKE :search_name OR email LIKE :search_email";
              $params[':search_name'] = '%' . $search . '%';
              $params[':search_email'] = '%' . $search . '%';
          }

          $countSql = "SELECT COUNT(*) FROM {$this->table}" . ($whereClause ? " {$whereClause}" : '');
          $countStmt = $this->db->prepare($countSql);
          $countStmt->execute($params);
          $total = (int) $countStmt->fetchColumn();

          $dataParams = array_merge([':limit' => $perPage, ':offset' => $offset], $params);
          $stmt = $this->db->prepare(
              "SELECT * FROM {$this->table}" . ($whereClause ? " {$whereClause}" : '') . " ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
          );
          $stmt->execute($dataParams);
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
     * Look up a registration by its qr_code identifier.
     */
    public function findByQrCode(string $qrCode): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE qr_code = :qr_code LIMIT 1");
        $stmt->execute(['qr_code' => $qrCode]);
        return $stmt->fetch();
    }

    /**
     * Mark a registration as attended (check-in).
     * Only sets attended_at if it is currently NULL — returns false if already checked in.
     */
    public function markAttended(int $id, ?string $attendedAt = null): bool
    {
        if ($attendedAt !== null && $attendedAt !== '') {
            $stmt = $this->db->prepare(
                "UPDATE {$this->table} SET attended_at = :attended_at WHERE id = :id AND attended_at IS NULL"
            );
            $stmt->execute(['attended_at' => $attendedAt, 'id' => $id]);
        } else {
            $stmt = $this->db->prepare(
                "UPDATE {$this->table} SET attended_at = NOW() WHERE id = :id AND attended_at IS NULL"
            );
            $stmt->execute(['id' => $id]);
        }

        return $stmt->rowCount() > 0;
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
