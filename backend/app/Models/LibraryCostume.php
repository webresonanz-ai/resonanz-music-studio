<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class LibraryCostume extends Model
{
    protected string $table = 'library_costumes';

    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function paginated(int $page = 1, int $perPage = 20, string $search = '', string $groupCategory = '', string $type = ''): array
    {
        $offset = ($page - 1) * $perPage;
        $conditions = [];
        $params = [];

        if ($search !== '') {
            $conditions[] = "(name LIKE :search OR group_category LIKE :search2 OR description LIKE :search3 OR costume_code LIKE :search4)";
            $params['search'] = "%{$search}%";
            $params['search2'] = "%{$search}%";
            $params['search3'] = "%{$search}%";
            $params['search4'] = "%{$search}%";
        }
        if ($groupCategory !== '') {
            $conditions[] = "group_category = :group_category";
            $params['group_category'] = $groupCategory;
        }
        if ($type !== '') {
            $conditions[] = "type = :type";
            $params['type'] = $type;
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $dataStmt = $this->db->prepare("SELECT * FROM {$this->table} {$where} ORDER BY name ASC LIMIT :limit OFFSET :offset");
        foreach ($params as $key => $value) {
            $dataStmt->bindValue(":{$key}", $value);
        }
        $dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $dataStmt->execute();
        $items = $dataStmt->fetchAll();

        $groupsStmt = $this->db->query("SELECT DISTINCT group_category FROM {$this->table} ORDER BY group_category ASC");
        $groups = $groupsStmt->fetchAll(PDO::FETCH_COLUMN);

        return [
            'data' => $items,
            'current_page' => $page,
            'per_page' => $perPage,
            'last_page' => max(1, (int) ceil($total / $perPage)),
            'total' => $total,
            'groups' => $groups,
        ];
    }
}
