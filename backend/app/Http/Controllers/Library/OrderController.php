<?php

namespace App\Http\Controllers\Library;

use App\Models\Order;
use App\Models\LibraryScore;

class OrderController
{
    private Order $order;
    private LibraryScore $score;

    public function __construct()
    {
        $this->order = new Order();
        $this->score = new LibraryScore();
    }

    public function index(): void
    {
        $userId = $this->getUserId();
        $orders = $this->order->byUser($userId);

        foreach ($orders as &$o) {
            $o['items'] = $this->order->items((int) $o['id']);
        }

        $this->json(['data' => $orders]);
    }

    public function show(string $id): void
    {
        $userId = $this->getUserId();
        $order = $this->order->find((int) $id);

        if (!$order || (int) $order['user_id'] !== $userId) {
            $this->json(['error' => 'Order not found'], 404);
            return;
        }

        $order['items'] = $this->order->items((int) $order['id']);
        $this->json(['data' => $order]);
    }

    public function store(): void
    {
        $data = $this->input();

        if (empty($data['items']) || !is_array($data['items'])) {
            $this->json(['error' => 'Cart is empty'], 422);
            return;
        }

        $userId = $this->getUserId();
        $totalAmount = 0;
        $lineItems = [];

        foreach ($data['items'] as $item) {
            $scoreId = (int) ($item['score_id'] ?? 0);
            $score = $this->score->find($scoreId);

            if (!$score) {
                $this->json(['error' => "Score ID {$scoreId} not found"], 422);
                return;
            }

            $price = (float) ($score['price'] ?? 0);
            $totalAmount += $price;

            $lineItems[] = [
                'score_id' => $scoreId,
                'title' => $score['title'],
                'composer' => $score['composer'] ?? '',
                'price' => $price,
            ];
        }

        $orderNumber = $this->order->generateOrderNumber();
        $orderId = $this->order->create([
            'user_id' => $userId,
            'order_number' => $orderNumber,
            'status' => 'pending_payment',
            'total_amount' => $totalAmount,
            'buyer_name' => $data['buyer_name'] ?? '',
            'buyer_email' => $data['buyer_email'] ?? '',
            'notes' => $data['notes'] ?? null,
        ]);

        foreach ($lineItems as $li) {
            $li['order_id'] = $orderId;
            $this->order->createItem($li);
        }

        $order = $this->order->find($orderId);
        $order['items'] = $this->order->items($orderId);

        $this->json(['success' => true, 'message' => 'Order created', 'data' => $order], 201);
    }

    private function getUserId(): int
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

        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM users WHERE api_token = :token AND (api_token_exp IS NULL OR api_token_exp > NOW())");
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();

        if (!$user) {
            $this->json(['error' => 'Unauthorized'], 401);
            exit;
        }

        return (int) $user['id'];
    }

    private function input(): array
    {
        $body = file_get_contents('php://input');
        $parsed = $body ? json_decode($body, true) : null;
        return is_array($parsed) ? $parsed : [];
    }

    private function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
