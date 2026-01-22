<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class PaymentService
{
    public function generateReference(): string
    {
        return 'FA-' . uniqid();
    }

    public function initializePaystack(string $email, float $amount, string $reference): array
    {
        $paystackData = [
            'email' => $email,
            'amount' => (int)($amount * 100), // Paystack expects amount in kobo
            'reference' => $reference,
            'callback_url' => config('app.url') . '/payment/callback',
        ];
        $response = Http::withToken(Config::get('services.paystack.secret_key'))
            ->post(Config::get('services.paystack.payment_url') . '/transaction/initialize', $paystackData);
        if ($response->successful() && isset($response['data']['authorization_url'])) {
            return [
                'success' => true,
                'authorization_url' => $response['data']['authorization_url'],
                'message' => null,
            ];
        }
        return [
            'success' => false,
            'authorization_url' => null,
            'message' => $response['message'] ?? 'Paystack initialization failed.',
        ];
    }

    public function verifyPaystack(string $reference): array
    {
        $verifyUrl = Config::get('services.paystack.payment_url') . '/transaction/verify/' . $reference;
        $response = Http::withToken(Config::get('services.paystack.secret_key'))->get($verifyUrl);
        if ($response->successful() && isset($response['data']['status']) && $response['data']['status'] === 'success') {
            return [
                'success' => true,
                'data' => $response['data'],
                'message' => null,
            ];
        }
        return [
            'success' => false,
            'data' => null,
            'message' => $response['message'] ?? 'Paystack verification failed.',
        ];
    }
}
