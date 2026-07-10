<?php

namespace App\Models;

use App\Core\Model;

class News extends Model
{
    protected string $table = 'news';

    public function all(): array
    {
        $stmt = $this->db->query("
            SELECT n.*,
                   (SELECT GROUP_CONCAT(np.program_id) FROM news_programs np WHERE np.news_id = n.id) as program_ids
            FROM {$this->table} n
            ORDER BY n.published_at DESC
        ");
        $results = $stmt->fetchAll();
        foreach ($results as &$row) {
            $row['program_ids'] = $row['program_ids'] ? explode(',', $row['program_ids']) : [];
        }
        return $results;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT n.*,
                   (SELECT GROUP_CONCAT(np.program_id) FROM news_programs np WHERE np.news_id = n.id) as program_ids
            FROM {$this->table} n
            WHERE n.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch() ?: null;
        if ($result) {
            $result['program_ids'] = $result['program_ids'] ? explode(',', $result['program_ids']) : [];
        }
        return $result;
    }

    public function findByProgram(string $programId): array
    {
        $stmt = $this->db->prepare("
            SELECT n.*,
                   (SELECT GROUP_CONCAT(np.program_id) FROM news_programs np WHERE np.news_id = n.id) as program_ids
            FROM {$this->table} n
            WHERE n.id IN (
                SELECT news_id FROM news_programs WHERE program_id = :program_id
            )
            ORDER BY n.published_at DESC
        ");
        $stmt->execute(['program_id' => $programId]);
        $results = $stmt->fetchAll();
        foreach ($results as &$row) {
            $row['program_ids'] = $row['program_ids'] ? explode(',', $row['program_ids']) : [];
        }
        return $results;
    }

    public function syncPrograms(int $newsId, array $programIds): void
    {
        $stmt = $this->db->prepare("DELETE FROM news_programs WHERE news_id = :news_id");
        $stmt->execute(['news_id' => $newsId]);

        if (!empty($programIds)) {
            $stmt = $this->db->prepare("INSERT INTO news_programs (news_id, program_id) VALUES (:news_id, :program_id)");
            foreach ($programIds as $programId) {
                $stmt->execute([
                    'news_id' => $newsId,
                    'program_id' => $programId
                ]);
            }
        }
    }
}
