<?php

namespace App\Models;

use App\Core\Model;

class SeatHold extends Model
{
    protected string $table = 'seat_holds';

    private const TTL_MINUTES = 10;

    /**
     * Try to place a hold for a seat.
     * Returns ['ok' => true] on success or ['error' => '...'] on conflict.
     */
    public function hold(int $scheduleId, string $seatNumber, int $userId): array
    {
        // 1. Purge expired holds first (keeps the table clean)
        $this->purgeExpired();

        // 2. Check if the seat is already confirmed (registered)
        $stmt = $this->db->prepare("
            SELECT 1 FROM concert_audiences
            WHERE schedule_id = :sid AND seat_number = :seat
            LIMIT 1
        ");
        $stmt->execute(['sid' => $scheduleId, 'seat' => $seatNumber]);
        if ($stmt->fetch()) {
            return ['error' => 'Kursi ini sudah dipesan oleh orang lain.'];
        }

        // 3. Check if hold exists for this seat
        $stmt = $this->db->prepare("
            SELECT user_id FROM {$this->table}
            WHERE schedule_id = :sid AND seat_number = :seat
            LIMIT 1
        ");
        $stmt->execute(['sid' => $scheduleId, 'seat' => $seatNumber]);
        $existing = $stmt->fetch();

        if ($existing) {
            // If the same user holds it already, refresh the hold
            if ((int) $existing['user_id'] === $userId) {
                $this->refresh($scheduleId, $seatNumber, $userId);
                return ['ok' => true];
            }
            return ['error' => 'Kursi ini sedang dipesan oleh pengguna lain. Coba lagi dalam beberapa menit.'];
        }

        // 4. Check how many holds this user already has for this schedule
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS cnt FROM {$this->table}
            WHERE schedule_id = :sid AND user_id = :uid
        ");
        $stmt->execute(['sid' => $scheduleId, 'uid' => $userId]);
        $row = $stmt->fetch();
        if ((int) ($row['cnt'] ?? 0) >= 5) {
            return ['error' => 'Maksimal 5 kursi per transaksi.'];
        }

        // 5. Insert new hold — store in UTC so the frontend can parse it unambiguously
        $expiresAt = gmdate('Y-m-d H:i:s', strtotime('+' . self::TTL_MINUTES . ' minutes'));
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (schedule_id, seat_number, user_id, expires_at)
            VALUES (:sid, :seat, :uid, :exp)
        ");
        $stmt->execute([
            'sid'  => $scheduleId,
            'seat' => $seatNumber,
            'uid'  => $userId,
            'exp'  => $expiresAt,
        ]);

        return ['ok' => true, 'expires_at' => $expiresAt];
    }

    /**
     * Release a specific hold by the owning user.
     */
    public function release(int $scheduleId, string $seatNumber, int $userId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM {$this->table}
            WHERE schedule_id = :sid AND seat_number = :seat AND user_id = :uid
        ");
        return $stmt->execute(['sid' => $scheduleId, 'seat' => $seatNumber, 'uid' => $userId]);
    }

    /**
     * Release ALL holds for a user on a given schedule (called after successful registration).
     */
    public function releaseAll(int $scheduleId, int $userId): void
    {
        $stmt = $this->db->prepare("
            DELETE FROM {$this->table}
            WHERE schedule_id = :sid AND user_id = :uid
        ");
        $stmt->execute(['sid' => $scheduleId, 'uid' => $userId]);
    }

    /**
     * Get all active held seat numbers for a schedule (for the seats endpoint).
     * Returns array of seat_number strings.
     */
    public function getHeldSeats(int $scheduleId): array
    {
        $this->purgeExpired();
        $stmt = $this->db->prepare("
            SELECT seat_number FROM {$this->table}
            WHERE schedule_id = :sid
        ");
        $stmt->execute(['sid' => $scheduleId]);
        return array_column($stmt->fetchAll(), 'seat_number');
    }

    /**
     * Get held seat numbers for a specific user on a schedule.
     */
    public function getUserHolds(int $scheduleId, int $userId): array
    {
        $this->purgeExpired();
        $stmt = $this->db->prepare("
            SELECT seat_number, expires_at FROM {$this->table}
            WHERE schedule_id = :sid AND user_id = :uid
            ORDER BY held_at ASC
        ");
        $stmt->execute(['sid' => $scheduleId, 'uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Refresh expiry for an existing hold (re-clicked by same user).
     */
    private function refresh(int $scheduleId, string $seatNumber, int $userId): void
    {
        $expiresAt = gmdate('Y-m-d H:i:s', strtotime('+' . self::TTL_MINUTES . ' minutes'));
        $stmt = $this->db->prepare("
            UPDATE {$this->table} SET expires_at = :exp
            WHERE schedule_id = :sid AND seat_number = :seat AND user_id = :uid
        ");
        $stmt->execute([
            'exp'  => $expiresAt,
            'sid'  => $scheduleId,
            'seat' => $seatNumber,
            'uid'  => $userId,
        ]);
    }

    /**
     * Delete all rows where expires_at <= NOW().
     */
    private function purgeExpired(): void
    {
        $this->db->exec("DELETE FROM {$this->table} WHERE expires_at <= UTC_TIMESTAMP()");
    }
}
