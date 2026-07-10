<?php

namespace App\Models;

use App\Core\Model;

class Member extends Model
{
    protected string $table = 'members';

    protected array $fillable = [
        'program_id',
        'name',
        'nickname',
        'email',
        'stage_name',
        'birth_place',
        'birth_date',
        'domicile',
        'phone',
        'year_join',
        'field_of_work',
        'role',
        'section',
        'join_date',
        'status',
        'performances',
        'avatar_url',
    ];

    private const DEFAULT_AVATAR = 'https://voca-land.sgp1.cdn.digitaloceanspaces.com/0/1757684222527/9465e2e8.jpg';
    private const VALID_ROLES = ['Sopran', 'Alto', 'Tenor', 'Bass'];
    private const VALID_STATUSES = ['active', 'passive'];

    // ── Read ──────────────────────────────────────────────────────────────

    /**
     * Return all members for a given program (all statuses).
     */
    public function findByProgram(string $programId): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, name, nickname, email, stage_name, birth_place, birth_date,
                     domicile, phone, year_join, field_of_work, role, section,
                     join_date, status, performances, avatar_url, created_at, updated_at
             FROM {$this->table}
             WHERE program_id = :program_id
             ORDER BY name ASC"
        );
        $stmt->execute(['program_id' => $programId]);
        return $stmt->fetchAll();
    }

    // ── Validate ──────────────────────────────────────────────────────────

    public function validate(array $data, bool $requireName = true): ?string
    {
        if ($requireName && empty(trim($data['name'] ?? ''))) {
            return 'Name is required';
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return 'Invalid email address';
        }

        if (!empty($data['role']) && !in_array($data['role'], self::VALID_ROLES, true)) {
            return 'Role must be one of: ' . implode(', ', self::VALID_ROLES);
        }

        if (!empty($data['status']) && !in_array($data['status'], self::VALID_STATUSES, true)) {
            return 'Status must be active or passive';
        }

        if (!empty($data['year_join']) && !preg_match('/^\d{4}$/', trim($data['year_join']))) {
            return 'year_join must be a valid 4-digit year';
        }

        if (!empty($data['performances']) && (!is_numeric($data['performances']) || (int) $data['performances'] < 0)) {
            return 'performances must be a non-negative integer';
        }

        return null;
    }

    // ── Sanitize ──────────────────────────────────────────────────────────

    public function sanitize(array $raw): array
    {
        return [
            'name' => trim($raw['name'] ?? ''),
            'nickname' => trim($raw['nickname'] ?? '') ?: null,
            'email' => strtolower(trim($raw['email'] ?? '')) ?: null,
            'stage_name' => trim($raw['stage_name'] ?? '') ?: null,
            'birth_place' => trim($raw['birth_place'] ?? '') ?: null,
            'birth_date' => $this->sanitizeDate($raw['birth_date'] ?? null),
            'domicile' => trim($raw['domicile'] ?? '') ?: null,
            'phone' => trim($raw['phone'] ?? '') ?: null,
            'year_join' => trim($raw['year_join'] ?? '') ?: null,
            'field_of_work' => trim($raw['field_of_work'] ?? '') ?: null,
            'role' => in_array($raw['role'] ?? '', self::VALID_ROLES, true) ? $raw['role'] : null,
            'section' => trim($raw['section'] ?? '') ?: null,
            'join_date' => $this->sanitizeDate($raw['join_date'] ?? null),
            'status' => in_array($raw['status'] ?? '', self::VALID_STATUSES, true) ? $raw['status'] : 'active',
            'performances' => isset($raw['performances']) && $raw['performances'] !== '' ? (int) $raw['performances'] : 0,
            'avatar_url' => trim($raw['avatar_url'] ?? '') ?: self::DEFAULT_AVATAR,
        ];
    }

    private function sanitizeDate(?string $value): ?string
    {
        if (empty($value))
            return null;
        $ts = strtotime($value);
        return $ts !== false ? date('Y-m-d', $ts) : null;
    }
}
