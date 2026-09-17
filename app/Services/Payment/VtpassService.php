<?php

namespace App\Services\Payment;

use App\Models\AppUser;
use App\Models\PaymentJournalEntry;
use App\Models\VtpassPayment;
use App\Models\Wallet;
use App\Services\Wallet\WalletService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VtpassService
{
    public function __construct(
        private readonly Client $http,
        private readonly WalletService $walletService,
        private readonly PaymentRailService $railService,
        private readonly string $apiKey,
        private readonly string $secretKey,
        private readonly string $publicKey,
        private readonly string $baseUrl,
    ) {}

    /**
     * Initiate a VTPass bill payment. Returns the VtpassPayment record.
     */
    public function initiate(
        AppUser $user,
        Wallet $wallet,
        string $serviceId,
        int $amountKobo,
        string $phone,
        ?string $billersCode = null,
        ?string $variationCode = null,
        array $extra = [],
    ): VtpassPayment {
        
        set_time_limit(120);

        $idempotencyKey = $extra['idempotency_key'] ?? Str::uuid()->toString();

        // Check existing by idempotency key
        $existing = VtpassPayment::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        $paymentReference = 'FP' . now()->format('YmdHis') . strtoupper(Str::random(6));
        $rail = $this->railService->getActiveRail('VTPASS', $user->tenant_id);
        $feeKobo = $rail ? $this->railService->calculateFee($rail, $amountKobo) : 0;

        return DB::transaction(function () use (
            $user, $wallet, $serviceId, $amountKobo, $phone,
            $billersCode, $variationCode, $paymentReference, $idempotencyKey,
            $feeKobo, $rail
        ) {
            $totalDebit = $amountKobo + $feeKobo;

            // Debit wallet (raises exception if insufficient)
            $this->walletService->debit($wallet, $totalDebit, $paymentReference, "Bill payment: {$serviceId}");

            $payment = VtpassPayment::create([
                'user_id'          => $user->id,
                'wallet_id'        => $wallet->id,
                'tenant_id'        => $user->tenant_id,
                'payment_reference'=> $paymentReference,
                'idempotency_key'  => $idempotencyKey,
                'service_id'       => $serviceId,
                'variation_code'   => $variationCode,
                'amount_kobo'      => $amountKobo,
                'fee_kobo'         => $feeKobo,
                'phone'            => $phone,
                'billersCode'      => $billersCode,
                'payment_status'   => 'PENDING',
                'processor_id'     => $rail?->processor_id,
                'processor_fee_kobo' => $feeKobo,
                // Store extra metadata (subscription_type etc.) for use at submit time
                'request_payload'  => array_filter([
                    'subscription_type' => $extra['subscription_type'] ?? null,
                ], fn ($v) => $v !== null),
            ]);

            $this->log($payment, 'DEBIT', 'SUCCESS', ['total_debit_kobo' => $totalDebit]);

            return $payment;
        });
    }

    /**
     * Submit payment to VTPass API.
     */
    public function submit(VtpassPayment $payment): VtpassPayment
    {
        Log::info('Before submit');

        $payment->update(['payment_status' => 'PROCESSING']);
        $this->log($payment, 'SUBMIT', 'PROCESSING');

        try {
            $requestId = now()->format('YmdHis') . $payment->payment_reference;
            $payload = [
                'request_id'        => $requestId,
                'serviceID'         => $payment->service_id,
                'amount'            => (int) round($payment->amount_kobo / 100),
                'phone'             => $payment->phone,
                'billersCode'       => $payment->billersCode,
                'variation_code'    => $payment->variation_code,
                // Include subscription_type for TV services if stored at initiation
                'subscription_type' => $payment->request_payload['subscription_type'] ?? null,
            ];



            Log::info('VTPass API Request', [
                'api_key' => config('services.vtpass.api_key'),
                'secret_key' => config('services.vtpass.secret_key'),
                'public_key' => config('services.vtpass.public_key'),
                'base_url' => config('services.vtpass.base_url'),
                'payload' => $payload
            ]);
            
            $response = $this->http->post("{$this->baseUrl}/pay", [
                'headers' => [
                    'api-key' => $this->apiKey,
                    'secret-key' => $this->secretKey,
                    'public-key' => $this->publicKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => array_filter($payload, fn ($v) => $v !== null),
            ]);

            Log::info('After submit');

            $body = json_decode($response->getBody()->getContents(), true);
            $providerCode = $body['code'] ?? null;
            $isSuccess = in_array($providerCode, ['000', '099']);

            $payment->update([
                'payment_status' => $isSuccess ? 'COMPLETED' : 'FAILED',
                'provider_code' => $providerCode,
                'request_payload' => $payload,
                'response_payload' => $body,
                'token' => $body['content']['transactions']['token'] ?? null,
                'units' => $body['content']['transactions']['units'] ?? null,
                'completed_at' => $isSuccess ? now() : null,
                'failed_at' => ! $isSuccess ? now() : null,
            ]);

            $this->log($payment, 'RESPONSE', $isSuccess ? 'COMPLETED' : 'FAILED', $body);

            if (! $isSuccess) {
                $this->reversePayment($payment);
            }

        } catch (\Throwable $e) {
            $payment->update([
                'payment_status' => 'FAILED',
                'failed_at' => now(),
            ]);
            $this->log($payment, 'EXCEPTION', 'FAILED', ['error' => $e->getMessage()]);
            $this->reversePayment($payment);
            throw $e;
        }

        return $payment->fresh();
    }

    private function reversePayment(VtpassPayment $payment): void
    {
        $wallet = Wallet::find($payment->wallet_id);
        if ($wallet) {
            $totalRefund = $payment->amount_kobo + $payment->fee_kobo;
            $this->walletService->reverse($wallet, $totalRefund, $payment->payment_reference, "Reversal: {$payment->service_id}");
            $this->log($payment, 'REVERSAL', 'SUCCESS', ['refunded_kobo' => $totalRefund]);
        }
    }

    private function log(VtpassPayment $payment, string $step, string $status, array $metadata = []): void
    {
        PaymentJournalEntry::create([
            'payment_id' => $payment->id,
            'step' => $step,
            'status' => $status,
            'metadata' => $metadata,
            'actor' => 'system',
        ]);
    }
}
