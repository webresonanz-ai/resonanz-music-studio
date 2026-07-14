<?php

namespace App\Core;

class Midtrans
{
    public static function isProduction(): bool
    {
        $env = $_ENV['APP_ENV'] ?? 'development';
        if ($env === 'production') return true;
        if ($env === 'development') return false;
        return !str_contains($_ENV['MIDTRANS_SERVER_KEY'] ?? '', '-SB-');
    }

    public static function getSnapBaseUrl(): string
    {
        return self::isProduction()
            ? 'https://app.midtrans.com/snap/v1'
            : 'https://app.sandbox.midtrans.com/snap/v1';
    }

    public static function getApiBaseUrl(): string
    {
        return self::isProduction()
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2';
    }

    public static function createTransaction(array $params): array
    {
        $serverKey = $_ENV['MIDTRANS_SERVER_KEY'] ?? '';
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => self::getSnapBaseUrl() . '/transactions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($serverKey . ':'),
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $body = json_decode($response, true) ?? [];
        if ($httpCode !== 201) {
            $msg = $body['error_message'] ?? ($body['message'] ?? 'Failed to create Snap transaction');
            throw new \Exception($msg);
        }
        return $body;
    }

    public static function transactionStatus(string $orderId): array
    {
        $serverKey = $_ENV['MIDTRANS_SERVER_KEY'] ?? '';
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => self::getApiBaseUrl() . '/' . urlencode($orderId) . '/status',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($serverKey . ':'),
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        return json_decode($response, true) ?? [];
    }

    public static function cancelTransaction(string $orderId): array
    {
        $serverKey = $_ENV['MIDTRANS_SERVER_KEY'] ?? '';
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => self::getApiBaseUrl() . '/' . urlencode($orderId) . '/cancel',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => '{}',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($serverKey . ':'),
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        return json_decode($response, true) ?? [];
    }

    public static function verifySignature(array $notification): bool
    {
        $orderId = $notification['order_id'] ?? '';
        $statusCode = $notification['status_code'] ?? '';
        $grossAmount = $notification['gross_amount'] ?? '';
        $serverKey = $_ENV['MIDTRANS_SERVER_KEY'] ?? '';
        $hash = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        return $hash === ($notification['signature_key'] ?? '');
    }

    public static function mapStatus(string $transactionStatus, string $fraudStatus = 'accept'): string
    {
        return match ($transactionStatus) {
            'capture' => $fraudStatus === 'accept' ? 'paid' : 'cancelled',
            'settlement' => 'paid',
            'pending' => 'pending_payment',
            'deny', 'cancel', 'expire' => 'cancelled',
            'refund', 'partial_refund' => 'cancelled',
            default => 'pending_payment',
        };
    }
}
