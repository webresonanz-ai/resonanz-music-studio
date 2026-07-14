<?php

namespace App\Http\Controllers\Library;

use App\Models\Order;
use App\Models\LibraryScore;
use App\Core\Midtrans;

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
            if ($o['status'] === 'pending_payment') {
                $this->syncMidtransStatus($o);
            }
        }
        unset($o);

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

        if ($order['status'] === 'pending_payment') {
            $this->syncMidtransStatus($order);
        }

        $this->json(['data' => $order]);
    }

    private function syncMidtransStatus(array &$order): void
    {
        try {
            $status = Midtrans::transactionStatus($order['order_number']);
            $code = $status['status_code'] ?? '';

            if ($code === '404' || empty($status['transaction_status'])) {
                return;
            }

            $transStatus = $status['transaction_status'] ?? '';
            $fraudStatus = $status['fraud_status'] ?? 'accept';
            $newStatus = Midtrans::mapStatus($transStatus, $fraudStatus);

            if ($newStatus === 'paid' && $order['status'] !== 'paid') {
                $this->order->update((int) $order['id'], ['status' => 'paid']);
                $db = \App\Core\Database::getInstance();
                $db->prepare("UPDATE orders SET paid_at = NOW() WHERE id = :id")
                   ->execute(['id' => $order['id']]);
                $this->order->updatePayment((int) $order['id'], [
                    'transaction_id' => $status['transaction_id'] ?? null,
                    'payment_type' => $status['payment_type'] ?? null,
                ]);
                $order['status'] = 'paid';
                $order['paid_at'] = date('Y-m-d H:i:s');
            }
        } catch (\Exception $e) {
            // Midtrans API unreachable — serve cached status
        }
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

    public function snapToken(string $id): void
    {
        $orderId = (int) $id;
        $userId = $this->getUserId();
        $order = $this->order->find($orderId);

        if (!$order || (int) $order['user_id'] !== $userId) {
            $this->json(['error' => 'Order not found'], 404);
            return;
        }

        if ($order['status'] !== 'pending_payment') {
            $this->json(['error' => 'Order is not pending payment'], 422);
            return;
        }

        if (!empty($order['snap_token'])) {
            $this->json(['data' => ['snap_token' => $order['snap_token']]]);
            return;
        }

        $items = $this->order->items($orderId);
        $itemDetails = [];
        foreach ($items as $item) {
            $itemDetails[] = [
                'id' => (string) $item['score_id'],
                'price' => (int) $item['price'],
                'quantity' => 1,
                'name' => mb_substr($item['title'], 0, 50),
            ];
        }

        $params = [
            'transaction_details' => [
                'order_id' => $order['order_number'],
                'gross_amount' => (int) $order['total_amount'],
            ],
            'item_details' => $itemDetails,
            'credit_card' => [
                'secure' => true,
            ],
            'customer_details' => [
                'first_name' => $order['buyer_name'] ?: 'Customer',
                'email' => $order['buyer_email'] ?: '',
            ],
        ];

        try {
            $result = Midtrans::createTransaction($params);
            $snapToken = $result['token'] ?? '';
            if ($snapToken) {
                $this->order->saveSnapToken($orderId, $snapToken);
            }
            $this->json(['data' => [
                'snap_token' => $snapToken,
                'redirect_url' => $result['redirect_url'] ?? '',
            ]]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function cancel(string $id): void
    {
        $orderId = (int) $id;
        $userId = $this->getUserId();
        $order = $this->order->find($orderId);

        if (!$order || (int) $order['user_id'] !== $userId) {
            $this->json(['error' => 'Order not found'], 404);
            return;
        }

        if ($order['status'] !== 'pending_payment') {
            $this->json(['error' => 'Only pending orders can be cancelled'], 422);
            return;
        }

        try {
            $mtStatus = Midtrans::transactionStatus($order['order_number']);
            $code = $mtStatus['status_code'] ?? '';
            if ($code !== '404' && !empty($mtStatus['transaction_status'])) {
                $canCancel = in_array($mtStatus['transaction_status'], ['pending', 'deny', 'expire', 'cancel']);
                if ($canCancel) {
                    Midtrans::cancelTransaction($order['order_number']);
                }
            }
        } catch (\Exception $e) {
            // Midtrans unreachable — cancel locally anyway
        }

        $this->order->update($orderId, ['status' => 'cancelled']);
        $this->json(['success' => true, 'message' => 'Order cancelled']);
    }

    public function notification(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        if (empty($body['order_id']) || !Midtrans::verifySignature($body)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid signature']);
            exit;
        }

        $orderNumber = $body['order_id'];
        $orders = $this->order->where('order_number', $orderNumber);
        if (empty($orders)) {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
            exit;
        }

        $order = $orders[0];
        $newStatus = Midtrans::mapStatus(
            $body['transaction_status'] ?? '',
            $body['fraud_status'] ?? 'accept'
        );

        $this->order->update((int) $order['id'], ['status' => $newStatus]);

        $this->order->updatePayment((int) $order['id'], [
            'transaction_id' => $body['transaction_id'] ?? null,
            'payment_type' => $body['payment_type'] ?? null,
        ]);

        if ($newStatus === 'paid') {
            $stmt = \App\Core\Database::getInstance()->prepare(
                "UPDATE orders SET paid_at = NOW() WHERE id = :id"
            );
            $stmt->execute(['id' => $order['id']]);
        }

        $this->json(['success' => true]);
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
