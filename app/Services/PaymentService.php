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

    /**
     * Create a new subscription using saved card authorization (best practice)
     * This generates a proper SUB_xxx code immediately without new payment
     */
    public function createSubscription(string $customerCode, string $planCode, string $authorizationCode): array
    {
        $payload = [
            'customer' => $customerCode,
            'plan' => $planCode,
            'authorization' => $authorizationCode,
        ];

        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/subscription", $payload);

        $responseData = $response->json();
        if ($response->successful() && ($responseData['status'] ?? false)) {
            return [
                'success' => true,
                'data' => $responseData['data'] ?? null,
                'message' => $responseData['message'] ?? 'Subscription created successfully.',
            ];
        }

        return [
            'success' => false,
            'data' => null,
            'message' => $responseData['message'] ?? 'Unable to create subscription.',
        ];
    }

    /**
     * Enable a Paystack subscription by subscription code and token (legacy method)
     * Note: createSubscription() is preferred for new subscriptions
     */
    public function enableSubscription(string $subscriptionCode, string $token): array
    {
        $payload = [
            'code' => $subscriptionCode,
            'token' => $token,
        ];

        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/subscription/enable", $payload);

        $responseData = $response->json();
        if ($response->successful() && ($responseData['status'] ?? false)) {
            return [
                'success' => true,
                'message' => $responseData['message'] ?? 'Subscription enabled.',
            ];
        }

        return [
            'success' => false,
            'message' => $responseData['message'] ?? 'Unable to enable subscription.',
        ];
    }

    /**
     * Cancel a Paystack subscription by subscription code
     */
    public function cancelSubscription(string $subscriptionCode, ?string $authorizationCode = null): array
    {
        Log::info('Paystack cancelSubscription called', [
            'subscription_code' => $subscriptionCode,
            'authorization_code' => $authorizationCode,
        ]);
        $payload = [
            'code' => $subscriptionCode
        ];
        if (!empty($authorizationCode)) {
            $payload['token'] = $authorizationCode;
        }
        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/subscription/disable", $payload);

        $responseData = $response->json();
        if ($response->successful() && ($responseData['status'] ?? false)) {
            return [
                'success' => true,
                'message' => $response['message'] ?? 'Subscription cancelled.',
            ];
        }

        return [
            'success' => false,
            'message' => $response['message'] ?? 'Unable to cancel subscription.',
        ];
    }

    public function generateReference(): string
    {
        // Generate unique reference with timestamp for easy tracking
        return 'FA-' . strtoupper(\Illuminate\Support\Str::random(12)) . '-' . time();
    }

    /**
     * Initialize Paystack transaction (supports one-time + subscription)
     */
    public function initializePaystack(
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
        if ($amount !== null && $amount > 0) {
            $payload['amount'] = (int) ($amount * 100);
        }
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

    // Retry logic for timeouts: 3 attempts with increasing timeout
    $maxAttempts = 3;
    $attempt = 0;
    $response = null;

    while ($attempt < $maxAttempts) {
        $attempt++;
        $timeout = 15 + ($attempt * 5); // 20s, 25s, 30s

        try {
            $response = Http::withToken($this->secretKey)
                ->timeout($timeout)
                ->post("{$this->baseUrl}/transaction/initialize", $payload);

            if ($response->successful() && isset($response['data']['authorization_url'])) {
                return [
                    'success'           => true,
                    'authorization_url' => $response['data']['authorization_url'],
                    'message'           => null,
                ];
            }

            // If we get a response but it's not successful, break (don't retry)
            break;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning("Paystack initialize timeout (attempt {$attempt}/{$maxAttempts})", [
                'timeout' => $timeout,
                'error' => $e->getMessage(),
            ]);

            if ($attempt >= $maxAttempts) {
                return [
                    'success' => false,
                    'message' => 'Connection to payment gateway timed out. Please try again.',
                ];
            }

            // Wait before retry (exponential backoff)
            sleep($attempt);
        }
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

    public function fetchPlanDetails(string $planCode): array
    {
        $response = Http::withToken($this->secretKey)
            ->get("{$this->baseUrl}/plan/{$planCode}");

        if ($response->successful() && ($response['status'] ?? false)) {
            return [
                'success' => true,
                'data'    => $response['data'] ?? null,
                'message' => null,
            ];
        }

        return [
            'success' => false,
            'data'    => $response['data'] ?? null,
            'message' => $response['message'] ?? 'Plan lookup failed.',
        ];
    }

    public function fetchActiveSubscriptionByEmail(string $email): array
    {
        $response = Http::withToken($this->secretKey)
            ->get("{$this->baseUrl}/subscription", [
                'email' => $email,
            ]);

        if ($response->successful() && ($response['status'] ?? false)) {
            $subscriptions = $response['data'] ?? [];
            $active = collect($subscriptions)->firstWhere('status', 'active');

            return [
                'success' => true,
                'data'    => $active ?? null,
                'message' => null,
            ];
        }

        return [
            'success' => false,
            'data'    => $response['data'] ?? null,
            'message' => $response['message'] ?? 'Subscription lookup failed.',
        ];
    }

    /**
     * Fetch subscription by customer code (email token)
     * This is useful to get the subscription_code after payment
     */
    public function fetchSubscriptionByCustomer(string $customerCode): array
    {
        $response = Http::withToken($this->secretKey)
            ->get("{$this->baseUrl}/subscription", [
                'customer' => $customerCode,
            ]);

        if ($response->successful() && ($response['status'] ?? false)) {
            $subscriptions = $response['data'] ?? [];

            // Get the most recent active subscription
            $active = collect($subscriptions)
                ->where('status', 'active')
                ->sortByDesc('created_at')
                ->first();

            return [
                'success' => true,
                'data'    => $active ?? null,
                'all_subscriptions' => $subscriptions,
                'message' => null,
            ];
        }

        return [
            'success' => false,
            'data'    => null,
            'message' => $response['message'] ?? 'Customer subscription lookup failed.',
        ];
    }
}
