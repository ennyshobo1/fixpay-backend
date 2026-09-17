<?php

namespace App\Http\Controllers\Transfer;

use App\Http\Controllers\Controller;
use App\Models\Transfer;
use App\Services\Wallet\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaystackWebhookController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    /** POST /api/webhooks/paystack */
    public function handle(Request $request): JsonResponse
    {
        // Verify Paystack signature
        $secret = config('services.paystack.secret_key');
        $signature = $request->header('x-paystack-signature');
        $computed = hash_hmac('sha512', $request->getContent(), $secret);

        if (! hash_equals($computed, $signature ?? '')) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $event = $request->input('event');
        $data = $request->input('data');

        match ($event) {
            'transfer.success' => $this->onTransferSuccess($data),
            'transfer.failed'  => $this->onTransferFailed($data),
            'transfer.reversed' => $this->onTransferReversed($data),
            default => null,
        };

        return response()->json(['message' => 'OK']);
    }

    private function onTransferSuccess(array $data): void
    {
        $transfer = Transfer::where('transfer_reference', $data['reference'] ?? '')->first();
        if (! $transfer) {
            return;
        }

        $transfer->update([
            'status' => 'COMPLETED',
            'completed_at' => now(),
            'provider_response' => $data,
        ]);
    }

    private function onTransferFailed(array $data): void
    {
        $transfer = Transfer::where('transfer_reference', $data['reference'] ?? '')->first();
        if (! $transfer || $transfer->status === 'REVERSED') {
            return;
        }

        $transfer->update([
            'status' => 'FAILED',
            'failed_at' => now(),
            'failure_reason' => $data['reason'] ?? 'Unknown',
            'provider_response' => $data,
        ]);

        // Reverse wallet
        $wallet = $transfer->wallet;
        if ($wallet) {
            $this->walletService->reverse(
                $wallet,
                $transfer->amount_kobo + $transfer->fee_kobo,
                $transfer->transfer_reference,
                'Transfer failed reversal'
            );
        }
    }

    private function onTransferReversed(array $data): void
    {
        $transfer = Transfer::where('transfer_reference', $data['reference'] ?? '')->first();
        if (! $transfer) {
            return;
        }

        $transfer->update([
            'status' => 'REVERSED',
            'provider_response' => $data,
        ]);
    }
}
