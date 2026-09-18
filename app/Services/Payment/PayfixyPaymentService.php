<?php

namespace App\Services\Payment;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayfixyPaymentService
{
    protected ?string $baseUrl = null;
    protected ?string $secretKey = null;

    public function __construct()
    {
        $this->baseUrl = config('services.payfixy.base_url');
        $this->secretKey = config('services.payfixy.secret_key');
    }

    protected function client()
    {
        return Http::acceptJson()
        ->withToken($this->secretKey)
        ->timeout(30)
        ->retry(3, 1000);
    }

    /**
     * Initialize Payment
     */
    public function initializePayment(
        string $email,
        float $amount,
        string $callbackUrl
    ): array {

        try {

            $response = $this->client()->post(
                    "{$this->baseUrl}/api/v1/payment",
                    [
                        'email' => $email,

                        'amount' => $amount
                    ]
                );

            if (!$response->successful()) {

                throw new Exception($response->body());

            }

            $data = $response->json();

            if (
                !isset($data['status']) ||
                $data['status'] !== true
            ) {

                throw new Exception(
                    $data['message'] ?? 'Unable to initialize payment.'
                );

            }

            return $data;

        } catch (\Throwable $e) {

            Log::error('Initialize Payment Error', [

                'message' => $e->getMessage()

            ]);

            throw $e;

        }

    }

    /**
     * Verify Payment
     */
    public function verifyPayment(
        string $transactionReference
    ): array {

        try {

            $response = $this->client()->get(
                    "{$this->baseUrl}/api/v1/payment/verify/{$transactionReference}"
                );
                
            if (!$response->successful()) {

                throw new Exception(
                    $response->body()
                );

            }

            $data = $response->json();

            if (
                !isset($data['status']) ||
                $data['status'] !== true
            ) {

                throw new Exception(
                    $data['message'] ?? 'Unable to verify payment.'
                );

            }

            return $data;

        } catch (\Throwable $e) {

            Log::error('Verify Payment Error', [

                'transaction_reference' => $transactionReference,

                'message' => $e->getMessage(),

            ]);

            throw $e;

        }

    }
}