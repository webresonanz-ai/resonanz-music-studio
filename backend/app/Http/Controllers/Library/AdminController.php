<?php

namespace App\Http\Controllers\Library;

use App\Core\Database;

class AdminController
{
    public function orders(): void
    {
        $this->requireRole();
        $db = Database::getInstance();
        $orders = $db->query("
            SELECT o.*, u.name AS user_name, u.email AS user_email
            FROM orders o
            JOIN users u ON u.id = o.user_id
            ORDER BY o.created_at DESC
        ")->fetchAll();

        foreach ($orders as &$o) {
            $stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = :id ORDER BY id ASC");
            $stmt->execute(['id' => $o['id']]);
            $o['items'] = $stmt->fetchAll();
        }

        $this->json(['data' => $orders]);
    }

    public function profitShares(): void
    {
        $this->requireRole();
        $db = Database::getInstance();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $userId = (int) ($data['user_id'] ?? 0);
            $creatorShare = (float) ($data['creator_share'] ?? 40);
            $companyShare = (float) ($data['company_share'] ?? 60);

            if (!$userId) {
                $this->json(['error' => 'user_id is required'], 422);
                return;
            }

            $existing = $db->prepare("SELECT id FROM profit_shares WHERE user_id = :uid");
            $existing->execute(['uid' => $userId]);

            if ($existing->fetch()) {
                $stmt = $db->prepare("UPDATE profit_shares SET creator_share = :cs, company_share = :comps WHERE user_id = :uid");
            } else {
                $stmt = $db->prepare("INSERT INTO profit_shares (user_id, creator_share, company_share) VALUES (:uid, :cs, :comps)");
            }
            $stmt->execute(['uid' => $userId, 'cs' => $creatorShare, 'comps' => $companyShare]);

            $this->json(['success' => true]);
            return;
        }

        $shares = $db->query("
            SELECT ps.*, u.name, u.email, u.role
            FROM profit_shares ps
            JOIN users u ON u.id = ps.user_id
            ORDER BY u.name ASC
        ")->fetchAll();

        $creators = $db->query("
            SELECT id, name, email, role FROM users
            WHERE role IN ('composer', 'arranger')
            ORDER BY name ASC
        ")->fetchAll();

        $this->json(['data' => $shares, 'creators' => $creators]);
    }

    public function creatorProfit(string $userId): void
    {
        $this->requireRole();
        $db = Database::getInstance();
        $userId = (int) $userId;

        $user = $db->prepare("SELECT id, name, email, role FROM users WHERE id = :id");
        $user->execute(['id' => $userId]);
        $creator = $user->fetch();

        if (!$creator || !in_array(strtolower($creator['role']), ['composer', 'arranger'], true)) {
            $this->json(['error' => 'Creator not found'], 404);
            return;
        }

        $share = $db->prepare("SELECT creator_share, company_share FROM profit_shares WHERE user_id = :uid");
        $share->execute(['uid' => $userId]);
        $profitShare = $share->fetch();

        $creatorShare = $profitShare ? (float) $profitShare['creator_share'] : 40;
        $companyShare = $profitShare ? (float) $profitShare['company_share'] : 60;

        $stmt = $db->prepare("
            SELECT
                s.id AS score_id,
                s.title,
                s.composer,
                s.arranger,
                COUNT(DISTINCT oi.id) AS units_sold,
                COALESCE(SUM(oi.price), 0) AS gross_revenue,
                ROUND(COALESCE(SUM(oi.price), 0) * :cr / 100, 2) AS creator_profit,
                ROUND(COALESCE(SUM(oi.price), 0) * :comps / 100, 2) AS company_profit
            FROM library_scores s
            JOIN order_items oi ON oi.score_id = s.id
            JOIN orders o ON o.id = oi.order_id AND o.status = 'paid'
            WHERE s.created_by = :uid OR LOWER(s.composer) = LOWER(:uname) OR LOWER(s.arranger) = LOWER(:uname2)
            GROUP BY s.id
            ORDER BY creator_profit DESC
        ");
        $stmt->execute([
            'uid' => $userId,
            'uname' => $creator['name'],
            'uname2' => $creator['name'],
            'cr' => $creatorShare,
            'comps' => $companyShare,
        ]);
        $items = $stmt->fetchAll();

        $totalGross = 0;
        $totalCreatorProfit = 0;
        $totalCompanyProfit = 0;
        foreach ($items as &$item) {
            $item['units_sold'] = (int) $item['units_sold'];
            $item['gross_revenue'] = (float) $item['gross_revenue'];
            $item['creator_profit'] = (float) $item['creator_profit'];
            $item['company_profit'] = (float) $item['company_profit'];
            $totalGross += $item['gross_revenue'];
            $totalCreatorProfit += $item['creator_profit'];
            $totalCompanyProfit += $item['company_profit'];
        }

        $payoutStmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) AS total_paid FROM creator_payouts WHERE user_id = :uid");
        $payoutStmt->execute(['uid' => $userId]);
        $totalPaid = (float) $payoutStmt->fetch()['total_paid'];

        $this->json([
            'data' => [
                'creator' => $creator,
                'creator_share' => $creatorShare,
                'company_share' => $companyShare,
                'items' => $items,
                'total_paid_out' => $totalPaid,
                'balance' => round($totalCreatorProfit - $totalPaid, 2),
                'totals' => [
                    'gross_revenue' => round($totalGross, 2),
                    'creator_profit' => round($totalCreatorProfit, 2),
                    'company_profit' => round($totalCompanyProfit, 2),
                ],
            ],
        ]);
    }

    public function recordPayout(): void
    {
        $this->requireRole();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $userId = (int) ($data['user_id'] ?? 0);
        $amount = (float) ($data['amount'] ?? 0);
        $notes = trim($data['notes'] ?? '');

        if (!$userId || $amount <= 0) {
            $this->json(['error' => 'Valid user_id and amount required'], 422);
            return;
        }

        $db = Database::getInstance();

        $userCheck = $db->prepare("SELECT id FROM users WHERE id = :id AND role IN ('composer', 'arranger')");
        $userCheck->execute(['id' => $userId]);
        if (!$userCheck->fetch()) {
            $this->json(['error' => 'Creator not found'], 404);
            return;
        }

        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = '';
        if (str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
        }
        $stmt = $db->prepare("SELECT id FROM users WHERE api_token = :token");
        $stmt->execute(['token' => $token]);
        $admin = $stmt->fetch();
        $paidBy = $admin['id'];

        $insert = $db->prepare("INSERT INTO creator_payouts (user_id, amount, notes, paid_by) VALUES (:uid, :amt, :notes, :pb)");
        $insert->execute(['uid' => $userId, 'amt' => $amount, 'notes' => $notes, 'pb' => $paidBy]);

        $this->json(['success' => true, 'message' => 'Payout recorded']);
    }

    public function payoutHistory(string $userId): void
    {
        $this->requireRole();
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT cp.*, u.name AS paid_by_name
            FROM creator_payouts cp
            LEFT JOIN users u ON u.id = cp.paid_by
            WHERE cp.user_id = :uid
            ORDER BY cp.paid_at DESC
        ");
        $stmt->execute(['uid' => (int) $userId]);
        $payouts = $stmt->fetchAll();

        foreach ($payouts as &$p) {
            $p['amount'] = (float) $p['amount'];
        }

        $this->json(['data' => $payouts]);
    }

    private function requireRole(): void
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = '';
        if (str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
        }
        if (!$token) {
            $this->json(['error' => 'Unauthorized'], 401);
            exit;
        }
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, role FROM users WHERE api_token = :token AND (api_token_exp IS NULL OR api_token_exp > NOW())");
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();

        if (!$user || !in_array(strtolower($user['role']), ['admin', 'manager', 'manager_scores'], true)) {
            $this->json(['error' => 'Forbidden'], 403);
            exit;
        }
    }

    private function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
