<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-Idempotency-Key');

        if (!$key) {
            return response()->json(['message' => 'X-Idempotency-Key header is required.'], 400);
        }

        // Validate UUID format
        if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $key)) {
            return response()->json(['message' => 'X-Idempotency-Key must be a valid UUID.'], 400);
        }

        // Check if there is already an existing completed/processing payment
        $payment = \App\Models\VtpassPayment::where('idempotency_key', $key)->first();
        if ($payment) {
            if (in_array($payment->payment_status, ['PENDING', 'PROCESSING'])) {
                return response()->json(['message' => 'Transaction is currently processing.'], 409);
            }
            return response()->json([
                'payment_reference' => $payment->payment_reference,
                'status' => $payment->payment_status,
                'token' => $payment->token,
                'units' => $payment->units,
                'amount_kobo' => $payment->amount_kobo,
                'fee_kobo' => $payment->fee_kobo,
            ], $payment->payment_status === 'COMPLETED' ? 200 : 422);
        }

        // Check if there is already an existing completed/processing transfer
        $transfer = \App\Models\Transfer::where('idempotency_key', $key)->first();
        if ($transfer) {
            if (in_array($transfer->status, ['INITIATED', 'PROCESSING'])) {
                return response()->json(['message' => 'Transaction is currently processing.'], 409);
            }
            if ($transfer->transfer_type === 'BANK') {
                return response()->json([
                    'transfer_reference' => $transfer->transfer_reference,
                    'status' => $transfer->status,
                    'amount_kobo' => $transfer->amount_kobo,
                    'fee_kobo' => $transfer->fee_kobo,
                ], 202);
            } else {
                return response()->json([
                    'transfer_reference' => $transfer->transfer_reference,
                    'status' => $transfer->status,
                    'amount_kobo' => $transfer->amount_kobo,
                ]);
            }
        }

        // Acquire concurrent request lock
        $lockKey = "idempotency_lock:{$key}";
        if (!Cache::add($lockKey, true, 30)) {
            return response()->json(['message' => 'Concurrent request in progress.'], 409);
        }

        try {
            // Inject idempotency key into request inputs to preserve controller behavior
            $request->merge(['idempotency_key' => $key]);

            return $next($request);
        } finally {
            Cache::forget($lockKey);
        }
    }
}
