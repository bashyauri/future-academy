<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class PaymentService
{
    protected string $baseUrl;
    protected string $secretKey;

    public function __construct()
    {
        $this->baseUrl   = Config::get('services.paystack.payment_url', 'https://api.paystack.co');
        $this->secretKey = Config::get('services.paystack.secret_key');
    }

    public function generateReference(): string
    {
        // Better randomness + readability than uniqid()
        return 'FA-' . strtoupper(substr(md5(uniqid()), 0, 10)) . '-' . time();
    }

    /**
     * Initialize Paystack transaction (supports one-time + subscription)
     */public function initializePaystack(
    string $email,
    ?float $amount = null,
    string $reference,
    ?string $planCode = null,
    array $metadata = [],
    ?string $callbackUrl = null
): array {
    $payload = [
        'email'     => $email,
        'reference' => $reference,
    ];

    // Use array for metadata (not object)
    if (!empty($metadata)) {
        $payload['metadata'] = $metadata;
    }

    if ($planCode) {
        $payload['plan'] = $planCode;
        $payload['amount'] = null;
        // Do NOT set 'amount' for recurring payments
    } elseif ($amount !== null && $amount > 0) {
        $payload['amount'] = (int) ($amount * 100);
    } else {
        return [
            'success' => false,
            'message' => 'Amount required for one-time payments',
        ];
    }

    if ($callbackUrl) {
        $payload['callback_url'] = $callbackUrl;
    }

    // Debug: log exact payload before send
    Log::debug('Paystack initialize payload', [
        'is_recurring' => !empty($planCode),
        'plan_code'    => $planCode,
        'payload'      => $payload,
    ]);

    $response = Http::withToken($this->secretKey)
        ->post("{$this->baseUrl}/transaction/initialize", $payload);

    if ($response->successful() && isset($response['data']['authorization_url'])) {
        return [
            'success'           => true,
            'authorization_url' => $response['data']['authorization_url'],
            'message'           => null,
        ];
    }

    // Improved error return — capture full Paystack response
    $errorData = $response->json();
    Log::error('Paystack initialize failed', [
        'status'   => $response->status(),
        'response' => $errorData,
        'payload'  => $payload,
    ]);

    return [
        'success'           => false,
        'authorization_url' => null,
        'message'           => $errorData['message'] ?? 'Unable to initialize payment.',
        'error_code'        => $errorData['status'] ?? null,
        'full_response'     => $errorData,
    ];
}

    public function verifyPaystack(string $reference): array
    {
        $response = Http::withToken($this->secretKey)
            ->get("{$this->baseUrl}/transaction/verify/{$reference}");

        if ($response->successful() && isset($response['data']['status']) && $response['data']['status'] === 'success') {
            return [
                'success' => true,
                'data'    => $response['data'],
                'message' => null,
            ];
        }

        return [
            'success' => false,
            'data'    => $response['data'] ?? null,
            'message' => $response['message'] ?? 'Verification failed.',
        ];
    }
}
