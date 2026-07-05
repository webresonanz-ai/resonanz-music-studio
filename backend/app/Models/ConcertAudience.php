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

    /**
     * Count registrations for a specific concert schedule.
     */
    public function countBySchedule(int $scheduleId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE schedule_id = :schedule_id");
        $stmt->execute(['schedule_id' => $scheduleId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Check whether a guest with the same name + email has already registered
     * for a given schedule (case-insensitive).
     */
    public function existsByNameEmailAndSchedule(string $name, string $email, int $scheduleId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM {$this->table}
             WHERE LOWER(name) = LOWER(:name)
               AND LOWER(email) = LOWER(:email)
               AND schedule_id = :schedule_id
             LIMIT 1"
        );
        $stmt->execute([
            'name'        => trim($name),
            'email'       => trim($email),
            'schedule_id' => $scheduleId,
        ]);
        return (int) $stmt->fetchColumn() > 0;
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

    public function findPendingEmail(int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE send_email_status = 'pending' ORDER BY created_at DESC LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
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
     * Store the ticket email delivery result.
     */
    public function updateSendEmailStatus(int $id, string $status): bool
    {
        $allowed = ['pending', 'sent', 'failed'];
        if (!in_array($status, $allowed, true)) {
            $status = 'pending';
        }

        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET send_email_status = :status WHERE id = :id"
        );
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    /**
     * Build the unique QR code identifier string.
     *
     * Format: {concertCode}_{id}_{timestamp}_{rand4}
     * Example: SDG_42_1751234567_A3KZ
     */
    public static function buildQrCode(string $concertCode, int $id): string
    {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', trim($concertCode)));
        if ($code === '') {
            $code = 'CONCERT';
        }

        $timestamp = time();

        // 4-character alphanumeric random suffix (A-Z, 0-9)
        $chars  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $rand4  = '';
        for ($i = 0; $i < 4; $i++) {
            $rand4 .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return "{$code}_{$id}_{$timestamp}_{$rand4}";
    }
}
