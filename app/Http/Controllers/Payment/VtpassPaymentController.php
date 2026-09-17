<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\Payment\VtpassService;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VtpassPaymentController extends Controller
{
    public function __construct(
        private readonly VtpassService $vtpass,
    ) {}

    /** GET /api/payments/vtpass/services */
    public function services(Request $request): JsonResponse
    {
        $identifier = $request->query('identifier', 'airtime');
        $client = new Client(['timeout' => 15, 'verify' => false]);

        $response = $client->get(config('services.vtpass.base_url') . '/services', [
            'headers' => [
                'api-key' => config('services.vtpass.api_key'),
                'public-key' => config('services.vtpass.public_key'),
            ],
            'query' => ['identifier' => $identifier],
        ]);

        return response()->json(
            json_decode($response->getBody()->getContents(), true)
        );
    }

    /** GET /api/payments/vtpass/variations */
    public function variations(Request $request): JsonResponse
    {
        $request->validate(['serviceID' => 'required|string']);

        $client = new Client(['timeout' => 15, 'verify' => false]);
        $response = $client->get(config('services.vtpass.base_url') . '/service-variations', [
            'headers' => [
                'api-key' => config('services.vtpass.api_key'),
                'secret-key' => config('services.vtpass.secret_key'),
                'public-key' => config('services.vtpass.public_key'),
            ],
            'query' => ['serviceID' => $request->query('serviceID')],
        ]);

        return response()->json(
            json_decode($response->getBody()->getContents(), true)
        );
    }

    /** POST /api/payments/verify */
    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'service_id' => 'required|string',
            'billers_code' => 'required|string',
            'type' => 'nullable|string',
        ]);

        $client = new Client(['timeout' => 15, 'verify' => false]);
        
        $payload = [
            'billersCode' => $data['billers_code'],
            'serviceID' => $data['service_id'],
        ];

        if (!empty($data['type'])) {
            $payload['type'] = $data['type'];
        }

        $response = $client->post(config('services.vtpass.base_url') . '/merchant-verify', [
            'headers' => [
                'api-key' => config('services.vtpass.api_key'),
                'secret-key' => config('services.vtpass.secret_key'),
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        return response()->json(
            json_decode($response->getBody()->getContents(), true)
        );
    }

    /** POST /api/payments/vtpass */
    public function pay(Request $request): JsonResponse
    {
        $data = $request->validate([
            'service_id'        => 'required|string',
            'amount_kobo'       => 'required|integer|min:100',
            'phone'             => 'required|string',
            'billers_code'      => 'nullable|string',
            'variation_code'    => 'nullable|string',
            'subscription_type' => 'nullable|in:renew,change',
            'idempotency_key'   => 'nullable|string|uuid',
        ]);

        $user = $request->user();
        $wallet = $user->wallet;

        if (! $wallet || $wallet->status !== 'ACTIVE') {
            return response()->json(['message' => 'Wallet not available.'], 422);
        }

        $payment = $this->vtpass->initiate(
            user: $user,
            wallet: $wallet,
            serviceId: $data['service_id'],
            amountKobo: $data['amount_kobo'],
            phone: $data['phone'],
            billersCode: $data['billers_code'] ?? null,
            variationCode: $data['variation_code'] ?? null,
            extra: [
                'idempotency_key'   => $data['idempotency_key'] ?? null,
                'subscription_type' => $data['subscription_type'] ?? null,
            ],
        );

        // Submit asynchronously via queue in production; sync here for simplicity
        $payment = $this->vtpass->submit($payment);

        return response()->json([
            'payment_reference' => $payment->payment_reference,
            'status' => $payment->payment_status,
            'token' => $payment->token,
            'units' => $payment->units,
            'amount_kobo' => $payment->amount_kobo,
            'fee_kobo' => $payment->fee_kobo,
            'provider_code' => $payment->provider_code,
            'vtpass_code' => $payment->provider_code,
            'message' => $payment->payment_status === 'FAILED' ? ($payment->response_payload['response_description'] ?? 'Transaction failed.') : null,
        ], $payment->payment_status === 'COMPLETED' ? 200 : 422);
    }

    /** GET /api/payments/vtpass/{reference} */
    public function status(Request $request, string $reference): JsonResponse
    {
        $payment = \App\Models\VtpassPayment::where('payment_reference', $reference)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'payment_reference' => $payment->payment_reference,
            'service_id' => $payment->service_id,
            'amount_kobo' => $payment->amount_kobo,
            'fee_kobo' => $payment->fee_kobo,
            'status' => $payment->payment_status,
            'token' => $payment->token,
            'units' => $payment->units,
            'provider_code' => $payment->provider_code,
            'vtpass_code' => $payment->provider_code,
            'completed_at' => $payment->completed_at,
            'failed_at' => $payment->failed_at,
        ]);
    }
}
