<?php

namespace App\Http\Controllers\Wallet;

use App\Services\Wallet\WalletService;
use App\Services\Wallet\DigitalBankingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    public function __construct(protected WalletService $walletService, protected DigitalBankingService $digitalBankingService)
    {}

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([

            'bvn' => 'required|string',

            'nin' => 'required|string',

            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

            'dateOfBirth' => 'required|string',

            'gender' => 'required|integer',

            'lastName' => 'required|string',

            'otherNames' => 'required|string',

            'accountName' => 'required|string',

            'phoneNo' => 'required|string',

            'placeOfBirth' => 'nullable|string',

            'address' => 'required|string',

            'email' => 'required|email',

            'ninUserId' => [
                'nullable',
                'regex:/^[A-Z]{6}-\d{4}$/'
            ],

            'nextOfKinPhoneNo' => 'nullable|string',

            'nextOfKinName' => 'nullable|string',

        ]);

        try {

            $wallet = $this->walletService
                ->createWallet(
                    $request->user(),
                    $validated
                );

            return response()->json([

                'status' => true,

                'message' => 'Wallet created successfully.',

                'data' => $wallet,

            ]);

        }

        catch (\Throwable $e) {

            Log::error(

                'Create Wallet Controller Error',

                [

                    'user_id' => $request->user()->id,

                    'message' => $e->getMessage(),

                ]

            );

            return response()->json([

                'status' => false,

                'message' => $e->getMessage(),

            ],422);

        }

    }

    /** GET /api/wallet */
    public function show(Request $request): JsonResponse
    {
        $wallet = $request->user()->wallet;

        if (! $wallet) {
            return response()->json(['message' => 'Wallet not found.'], 404);
        }

        return response()->json([
            'id' => $wallet->id,
            'balance_kobo' => $wallet->balance_kobo,
            'ledger_balance_kobo' => $wallet->ledger_balance_kobo,
            'currency' => $wallet->currency,
            'status' => $wallet->status,
            'virtual_account_number' => $wallet->virtual_account_number,
            'virtual_account_bank' => $wallet->virtual_account_bank,
            'virtual_account_bank_code' => $wallet->virtual_account_bank_code,
        ]);
    }

    /** GET /api/wallet/transactions */
    public function transactions(Request $request): JsonResponse
    {
        $wallet = $request->user()->wallet;

        if (! $wallet) {
            return response()->json(['message' => 'Wallet not found.'], 404);
        }

        $perPage = $request->input('per_page') ?? $request->input('size') ?? 20;

        $entries = QueryBuilder::for($wallet->ledgerEntries())
            ->allowedFilters(['entry_type', 'correlation_id'])
            ->allowedSorts(['created_at'])
            ->defaultSort('-created_at')
            ->paginate($perPage);

        return response()->json($entries);
    }

    public function upgradeTier3(Request $request)
    {
        $request->validate([
            'accountNumber' => 'required',
            'bvn' => 'required',
            'nin' => 'required',
            'proofOfAddressVerification' => 'required|file',
        ]);

        return $this->digitalBankingService->upgradeWalletTier3(
            $request->accountNumber,
            $request->bvn,
            $request->nin,
            $request->file('proofOfAddressVerification')
        );
    }

    
    public function walletTransactions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'accountNumber' => ['required', 'string'],
            'fromDate'      => ['required', 'date'],
            'toDate'        => ['required', 'date'],
            'numberOfItems' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $response = $this->digitalBankingService->getWalletTransactions(
                accountNumber: $validated['accountNumber'],
                fromDate: $validated['fromDate'],
                toDate: $validated['toDate'],
                numberOfItems: $validated['numberOfItems'] ?? 100,
            );

            return response()->json($response);

        } catch (\Throwable $e) {

            Log::error('Wallet transaction fetch failed', [
                'accountNumber' => $validated['accountNumber'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'FAILED',
                'message' => 'Unable to retrieve wallet transactions.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function initiateUpgrade(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'accountNumber' => 'required|string',
            'bvn' => 'required|string',
            'nin' => 'required|string',
            'accountName' => 'required|string',
            'channelType' => 'required|string',
            'phoneNumber' => 'required|string',
            'tier' => 'required|string',
            'email' => 'required|email',

            'userPhoto' => 'required|image|max:2048',

            'idType' => 'required|string',
            'idNumber' => 'required|string',
            'idIssueDate' => 'required|date',
            'idExpiryDate' => 'nullable|date',

            'idCardFront' => 'required|image|max:2048',
            'idCardBack' => 'nullable|image|max:2048',

            'houseNumber' => 'required|string',
            'streetName' => 'required|string',
            'state' => 'required|string',
            'city' => 'required|string',
            'localGovernment' => 'required|string',

            'approvalStatus' => 'nullable|string',
            'pep' => 'required|string',

            'customerSignature' => 'required|image|max:2048',
            'utilityBill' => 'required|image|max:2048',

            'nearestLandmark' => 'required|string',
            'placeOfBirth' => 'required|string',

            'proofOfAddressVerification' => 'required|image|max:2048',
        ]);

        try {

            $response = $this->digitalBankingService->initiateWalletTier3Upgrade($validated);

            return response()->json([
                'status' => true,
                'message' => 'Wallet upgrade initiated successfully.',
                'data' => $response,
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
