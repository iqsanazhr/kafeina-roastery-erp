<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransQrisService
{
    protected string $serverKey;

    protected string $clientKey;

    protected bool $isProduction;

    protected string $merchantId;

    protected string $baseUrl;

    public function __construct()
    {
        $this->serverKey = (string) config('services.midtrans.server_key', '');
        $this->clientKey = (string) config('services.midtrans.client_key', '');
        $this->isProduction = (bool) config('services.midtrans.is_production', false);
        $this->merchantId = (string) config('services.midtrans.merchant_id', '');

        $this->baseUrl = $this->isProduction
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2';
    }

    /**
     * Create a dynamic QRIS transaction via Midtrans Core API.
     */
    public function createQrisCharge(
        string $orderNumber,
        float $grossAmount,
        array $items = [],
        string $customerName = 'Tamu Kafeina'
    ): array {
        // Prepare formatted items for Midtrans (max 50 chars for name, integer price)
        $formattedItems = [];
        $calculatedTotal = 0;

        foreach ($items as $item) {
            $price = (int) round((float) ($item['price'] ?? 0));
            $qty = max(1, (int) ($item['qty'] ?? 1));
            $name = mb_substr((string) ($item['name'] ?? 'Menu Kopi'), 0, 48);

            if ($price > 0 && $qty > 0) {
                $formattedItems[] = [
                    'id' => (string) ($item['id'] ?? 'ITEM-'.count($formattedItems)),
                    'price' => $price,
                    'quantity' => $qty,
                    'name' => $name,
                ];
                $calculatedTotal += ($price * $qty);
            }
        }

        $grossInt = (int) round($grossAmount);

        // Adjust item details if total doesn't match grossInt (e.g. tax, discount)
        if ($calculatedTotal !== $grossInt) {
            $formattedItems = [
                [
                    'id' => 'KAFEINA-ORDER',
                    'price' => $grossInt,
                    'quantity' => 1,
                    'name' => 'Pesanan Kafeina POS ('.$orderNumber.')',
                ],
            ];
        }

        $payload = [
            'payment_type' => 'qris',
            'transaction_details' => [
                'order_id' => $orderNumber,
                'gross_amount' => $grossInt,
            ],
            'qris' => [
                'acquirer' => 'gopay',
            ],
            'item_details' => $formattedItems,
            'customer_details' => [
                'first_name' => $customerName,
                'email' => 'cashier@kafeina.id',
            ],
        ];

        try {
            $response = Http::withBasicAuth($this->serverKey, '')
                ->timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/charge", $payload);

            if ($response->successful()) {
                $data = $response->json();

                // Extract QR Code Image URL from actions
                $qrUrl = null;
                if (! empty($data['actions']) && is_array($data['actions'])) {
                    foreach ($data['actions'] as $action) {
                        if (($action['name'] ?? '') === 'generate-qr-code') {
                            $qrUrl = $action['url'] ?? null;
                            break;
                        }
                    }
                }

                $qrString = $data['qr_string'] ?? '';
                $transactionId = $data['transaction_id'] ?? '';
                $expiryTime = $data['expiry_time'] ?? now()->addMinutes(15)->format('Y-m-d H:i:s');

                // Cache status
                Cache::put("midtrans_qris_{$orderNumber}", [
                    'status' => 'pending',
                    'order_id' => $orderNumber,
                    'transaction_id' => $transactionId,
                    'gross_amount' => $grossInt,
                    'qr_string' => $qrString,
                    'qr_url' => $qrUrl,
                    'created_at' => now()->toIso8601String(),
                ], 1800);

                return [
                    'success' => true,
                    'order_id' => $orderNumber,
                    'transaction_id' => $transactionId,
                    'gross_amount' => $grossInt,
                    'qr_url' => $qrUrl,
                    'qr_string' => $qrString,
                    'expiry_time' => $expiryTime,
                    'is_sandbox' => ! $this->isProduction,
                    'simulator_url' => 'https://simulator.sandbox.midtrans.com/qris/index',
                ];
            }

            Log::warning('Midtrans QRIS Charge non-200 response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return $this->fallbackOfflineQris($orderNumber, $grossInt);
        } catch (\Throwable $e) {
            Log::error('Midtrans QRIS Charge error: '.$e->getMessage());

            return $this->fallbackOfflineQris($orderNumber, $grossInt);
        }
    }

    /**
     * Check transaction status directly from Midtrans or cache.
     */
    public function checkStatus(string $orderNumber): array
    {
        // 1. Check if already marked settled in Cache
        $cached = Cache::get("midtrans_qris_{$orderNumber}");
        if (is_array($cached) && ($cached['status'] ?? '') === 'settlement') {
            return [
                'status' => 'settlement',
                'paid' => true,
                'order_id' => $orderNumber,
                'simulated' => $cached['simulated'] ?? false,
            ];
        }

        // 2. Query Midtrans API
        try {
            $response = Http::withBasicAuth($this->serverKey, '')
                ->timeout(6)
                ->get("{$this->baseUrl}/{$orderNumber}/status");

            if ($response->successful()) {
                $data = $response->json();
                $status = $data['transaction_status'] ?? 'pending';

                if (in_array($status, ['settlement', 'capture'], true)) {
                    Cache::put("midtrans_qris_{$orderNumber}", array_merge(
                        is_array($cached) ? $cached : [],
                        ['status' => 'settlement', 'paid' => true, 'payment_time' => now()->toIso8601String()]
                    ), 1800);

                    return [
                        'status' => 'settlement',
                        'paid' => true,
                        'order_id' => $orderNumber,
                        'payment_type' => $data['payment_type'] ?? 'qris',
                        'settlement_time' => $data['settlement_time'] ?? now()->toIso8601String(),
                    ];
                }

                if (in_array($status, ['expire', 'cancel', 'deny'], true)) {
                    return [
                        'status' => $status,
                        'paid' => false,
                        'order_id' => $orderNumber,
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Silence network hiccups during quick polling
        }

        return [
            'status' => 'pending',
            'paid' => false,
            'order_id' => $orderNumber,
        ];
    }

    /**
     * Instantly mark an order as paid (for local sandbox simulation).
     */
    public function simulatePayment(string $orderNumber): array
    {
        $cached = Cache::get("midtrans_qris_{$orderNumber}", []);

        Cache::put("midtrans_qris_{$orderNumber}", array_merge(
            is_array($cached) ? $cached : [],
            [
                'status' => 'settlement',
                'paid' => true,
                'simulated' => true,
                'settlement_time' => now()->toIso8601String(),
            ]
        ), 1800);

        return [
            'success' => true,
            'status' => 'settlement',
            'paid' => true,
            'order_id' => $orderNumber,
            'message' => 'Pembayaran berhasil diverifikasi (Simulasi Sandbox)!',
        ];
    }

    /**
     * Create a dynamic Virtual Account / Bank Transfer transaction via Midtrans Core API.
     */
    public function createVaCharge(
        string $orderNumber,
        float $grossAmount,
        string $bank = 'bca',
        string $customerName = 'Tamu Kafeina'
    ): array {
        $grossInt = (int) round($grossAmount);
        $bank = strtolower(trim($bank));
        if (! in_array($bank, ['bca', 'bni', 'bri', 'permata', 'mandiri'], true)) {
            $bank = 'bca';
        }

        $payload = [
            'transaction_details' => [
                'order_id' => $orderNumber,
                'gross_amount' => $grossInt,
            ],
            'customer_details' => [
                'first_name' => $customerName,
                'email' => 'cashier@kafeina.id',
            ],
        ];

        if ($bank === 'mandiri') {
            $payload['payment_type'] = 'echannel';
            $payload['echannel'] = [
                'bill_info1' => 'Pesanan POS',
                'bill_info2' => 'Kafeina Roastery',
            ];
        } elseif ($bank === 'permata') {
            $payload['payment_type'] = 'permata';
        } else {
            $payload['payment_type'] = 'bank_transfer';
            $payload['bank_transfer'] = [
                'bank' => $bank,
            ];
        }

        try {
            $response = Http::withBasicAuth($this->serverKey, '')
                ->timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/charge", $payload);

            if ($response->successful()) {
                $data = $response->json();
                $vaNumber = '';
                $billerCode = null;

                if ($bank === 'mandiri') {
                    $vaNumber = $data['bill_key'] ?? '';
                    $billerCode = $data['biller_code'] ?? '70012';
                } elseif ($bank === 'permata') {
                    $vaNumber = $data['permata_va_number'] ?? '';
                } else {
                    $vaNumber = $data['va_numbers'][0]['va_number'] ?? '';
                }

                $transactionId = $data['transaction_id'] ?? '';
                $expiryTime = $data['expiry_time'] ?? now()->addDay()->format('Y-m-d H:i:s');

                Cache::put("midtrans_qris_{$orderNumber}", [
                    'status' => 'pending',
                    'order_id' => $orderNumber,
                    'transaction_id' => $transactionId,
                    'gross_amount' => $grossInt,
                    'va_number' => $vaNumber,
                    'biller_code' => $billerCode,
                    'bank' => strtoupper($bank),
                    'created_at' => now()->toIso8601String(),
                ], 86400);

                return [
                    'success' => true,
                    'order_id' => $orderNumber,
                    'transaction_id' => $transactionId,
                    'gross_amount' => $grossInt,
                    'bank' => strtoupper($bank),
                    'va_number' => $vaNumber,
                    'biller_code' => $billerCode,
                    'expiry_time' => $expiryTime,
                    'is_sandbox' => ! $this->isProduction,
                ];
            }

            Log::warning('Midtrans VA Charge non-200 response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return $this->fallbackOfflineVa($orderNumber, $grossInt, $bank);
        } catch (\Throwable $e) {
            Log::error('Midtrans VA Charge error: '.$e->getMessage());

            return $this->fallbackOfflineVa($orderNumber, $grossInt, $bank);
        }
    }

    /**
     * Fallback mock VA generation if sandbox/offline.
     */
    protected function fallbackOfflineVa(string $orderNumber, int $grossAmount, string $bank): array
    {
        $bankUpper = strtoupper($bank);
        $seed = abs(crc32($orderNumber)) % 100000000;
        $seedStr = str_pad((string) $seed, 8, '0', STR_PAD_LEFT);

        $vaNumber = match ($bank) {
            'bca' => '88012'.$seedStr,
            'bni' => '98801'.$seedStr,
            'bri' => '12888'.$seedStr,
            'permata' => '89812'.$seedStr,
            'mandiri' => '88012'.$seedStr,
            default => '88012'.$seedStr,
        };

        $billerCode = $bank === 'mandiri' ? '70012' : null;

        Cache::put("midtrans_qris_{$orderNumber}", [
            'status' => 'pending',
            'order_id' => $orderNumber,
            'gross_amount' => $grossAmount,
            'va_number' => $vaNumber,
            'biller_code' => $billerCode,
            'bank' => $bankUpper,
            'offline' => true,
        ], 86400);

        return [
            'success' => true,
            'order_id' => $orderNumber,
            'gross_amount' => $grossAmount,
            'bank' => $bankUpper,
            'va_number' => $vaNumber,
            'biller_code' => $billerCode,
            'expiry_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'is_sandbox' => true,
            'is_fallback' => true,
        ];
    }

    /**
     * Cancel pending QRIS on Midtrans.
     */
    public function cancelTransaction(string $orderNumber): bool
    {
        try {
            $response = Http::withBasicAuth($this->serverKey, '')
                ->timeout(5)
                ->post("{$this->baseUrl}/{$orderNumber}/cancel");

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Verify Midtrans Webhook SHA512 signature.
     */
    public function verifySignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool
    {
        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.$this->serverKey);

        return hash_equals($expected, $signatureKey);
    }

    /**
     * Graceful fallback: Generates standard EMVCo string if API call fails.
     */
    protected function fallbackOfflineQris(string $orderNumber, int $grossAmount): array
    {
        // Standar EMVCo Dynamic QRIS string
        $amountStr = (string) $grossAmount;
        $amountLen = str_pad((string) strlen($amountStr), 2, '0', STR_PAD_LEFT);

        // Base EMVCo without CRC
        $base = "00020101021226620014COM.GO-JEK.WWW011993600914362029571760210G6202957170303UKE51440014ID.CO.QRIS.WWW0215AID8637668465160303UKE52043089530336054{$amountLen}{$amountStr}5802ID5916Kafeina Roastery6007Jakarta61051219062200116{$orderNumber}6304";

        // CRC16-CCITT (0xFFFF)
        $crc = $this->calculateCrc16($base);
        $qrString = $base.$crc;
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&data='.urlencode($qrString);

        Cache::put("midtrans_qris_{$orderNumber}", [
            'status' => 'pending',
            'order_id' => $orderNumber,
            'gross_amount' => $grossAmount,
            'qr_string' => $qrString,
            'qr_url' => $qrUrl,
            'offline' => true,
        ], 1800);

        return [
            'success' => true,
            'order_id' => $orderNumber,
            'gross_amount' => $grossAmount,
            'qr_url' => $qrUrl,
            'qr_string' => $qrString,
            'expiry_time' => now()->addMinutes(15)->format('Y-m-d H:i:s'),
            'is_sandbox' => true,
            'is_fallback' => true,
            'simulator_url' => 'https://simulator.sandbox.midtrans.com/qris/index',
        ];
    }

    /**
     * CRC-16/CCITT-FALSE Checksum calculation for EMVCo QRIS.
     */
    protected function calculateCrc16(string $str): string
    {
        $crc = 0xFFFF;
        $len = strlen($str);

        for ($c = 0; $c < $len; $c++) {
            $crc ^= (ord($str[$c]) << 8);
            for ($i = 0; $i < 8; $i++) {
                if ($crc & 0x8000) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
