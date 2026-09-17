<?php

namespace App\Http\Controllers\Transfer;

use App\Http\Controllers\Controller;
use App\Models\Transfer;
use App\Services\Transfer\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function __construct(
        private readonly TransferService $transferService,
    ) {}

    /** POST /api/transfers/bank */
    public function toBank(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount_kobo'     => 'required|integer|min:10000',
            'account_number'  => 'required|string|size:10',
            'bank_code'       => 'required|string|max:10',
            'narration'       => 'nullable|string|max:100',
            'idempotency_key' => 'nullable|string|uuid',
        ]);

        $user = $request->user();
        $wallet = $user->wallet;

        if (! $wallet || $wallet->status !== 'ACTIVE') {
            return response()->json(['message' => 'Wallet not available.'], 422);
        }

        $transfer = $this->transferService->initiateBank(
            user: $user,
            wallet: $wallet,
            amountKobo: $data['amount_kobo'],
            accountNumber: $data['account_number'],
            bankCode: $data['bank_code'],
            narration: $data['narration'] ?? 'Transfer',
            idempotencyKey: $data['idempotency_key'] ?? null,
        );

        return response()->json([
            'transfer_reference' => $transfer->transfer_reference,
            'status' => $transfer->status,
            'amount_kobo' => $transfer->amount_kobo,
            'fee_kobo' => $transfer->fee_kobo,
        ], 202);
    }

    /** POST /api/transfers/wallet */
    public function toWallet(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount_kobo'        => 'required|integer|min:100',
            'recipient_phone'    => 'required|string',
            'narration'          => 'nullable|string|max:100',
            'idempotency_key'    => 'nullable|string|uuid',
        ]);

        $user = $request->user();
        $wallet = $user->wallet;

        if (! $wallet || $wallet->status !== 'ACTIVE') {
            return response()->json(['message' => 'Wallet not available.'], 422);
        }

        $transfer = $this->transferService->initiateWallet(
            user: $user,
            wallet: $wallet,
            amountKobo: $data['amount_kobo'],
            recipientPhone: $data['recipient_phone'],
            narration: $data['narration'] ?? 'Wallet transfer',
            idempotencyKey: $data['idempotency_key'] ?? null,
        );

        return response()->json([
            'transfer_reference' => $transfer->transfer_reference,
            'status' => $transfer->status,
            'amount_kobo' => $transfer->amount_kobo,
        ]);
    }

    /** GET /api/transfers/{reference} */
    public function status(Request $request, string $reference): JsonResponse
    {
        $transfer = Transfer::where('transfer_reference', $reference)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'transfer_reference' => $transfer->transfer_reference,
            'transfer_type' => $transfer->transfer_type,
            'amount_kobo' => $transfer->amount_kobo,
            'fee_kobo' => $transfer->fee_kobo,
            'status' => $transfer->status,
            'narration' => $transfer->narration,
            'bank_name' => $transfer->bank_name,
            'account_number' => $transfer->account_number,
            'account_name' => $transfer->account_name,
            'completed_at' => $transfer->completed_at,
            'failed_at' => $transfer->failed_at,
        ]);
    }

    /** GET /api/transfers */
    public function index(Request $request): JsonResponse
    {
        $transfers = Transfer::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        return response()->json($transfers);
    }
}
