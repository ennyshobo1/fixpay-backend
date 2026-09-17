<?php

namespace App\Services\Wallet;

use App\Models\AppUser;
use App\Models\LedgerEntry;
use App\Models\KycVerification;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use App\Models\Wallet;
use App\Services\Providus\ProvidusVirtualAccountAdapter;
use Illuminate\Support\Facades\DB;
use App\Services\Wallet\DigitalBankingService;

class WalletService
{
    public function __construct(
        private readonly ProvidusVirtualAccountAdapter $virtualAccount,
        protected DigitalBankingService $digitalBankingService,
    ) {}

    public function createWallet(AppUser $user, array $data): Wallet
    {
        DB::beginTransaction();

        try {

            /**
             * Prevent duplicate wallet
             */
            if ($user->wallet) {
                throw new \Exception('Wallet already exists.');
            }

            /**
             * Get verified BVN
             */
            // $bvn = KycVerification::where('user_id', $user->id)
            //         ->where('type', 'BVN')
            //         ->where('verification_status', 'VERIFIED')
            //         ->latest()
            //         ->first();

            // if (!$bvn) {
            //     throw new \Exception('BVN has not been verified.');
            // }

            // /**
            //  * Get verified NIN
            //  */
            // $nin = KycVerification::where('user_id', $user->id)
            //         ->where('type', 'NIN')
            //         ->where('verification_status', 'VERIFIED')
            //         ->latest()
            //         ->first();

            // if (!$nin) {
            //     throw new \Exception(
            //         'NIN has not been verified.'
            //     );
            // }

            // /**
            //  * Build request payload
            //  */

            // Log::info('Found verified BVN');

            // $bvnNumber = Crypt::decryptString($bvn->identifier);

            // Log::info('BVN decrypted');

            // $ninNumber = Crypt::decryptString($nin->identifier);

            // Log::info('NIN decrypted');

            // Verify BVN

            $transactionTrackingRef ='DGN'.random_int(10000,99999);

            $payload = [
                'bvn' => $data['bvn'],
                'nin' => $data['nin'],
                'type' => 'FACIAL',
                'image' => $data['image'],
                'phoneNo' => $data['phoneNo'],
                'transactionRef' => $transactionTrackingRef
            ];


            $response = $this->digitalBankingService
                ->verifyBVN($payload);

            /**
             * Validate response
             */

            if (
                strtoupper($response['status']) !== 'SUCCESS'
            ) {
                throw new \Exception($response['message']);
            }

            //Open Wallet
                
            $payload = [
                'BVN' => $data['bvn'],
                'nationalIdentityNo' => $data['nin'],
                'dateOfBirth' => $data['dateOfBirth'],
                'gender' => $data['gender'],
                'lastName' => $data['lastName'],
                'otherNames' => $data['otherNames'],
                'accountName' => $data['accountName'],
                'phoneNo' => $data['phoneNo'],
                'placeOfBirth' => $data['placeOfBirth'] ?? null,
                'address' => $data['address'],
                'email' => $data['email'],
                'ninUserId' => $data['ninUserId'] ?? null,
                'nextOfKinPhoneNo' => $data['nextOfKinPhoneNo'] ?? null,
                'nextOfKinName' => $data['nextOfKinName'] ?? null,
                'transactionTrackingRef' => $transactionTrackingRef
            ];

            Log::info(
                'This is the payload for Wallet Creation',
                [
                    'payload' => $payload,
                ]
            );

            /**
             * Create wallet remotely
             */

            $response = $this->digitalBankingService
                ->openWallet($payload);

            /**
             * Validate response
             */

            if (
                strtoupper($response['status']) !== 'SUCCESS'
            ) {
                throw new \Exception($response['message']);
            }

            /**
             * Save wallet
             */
            $wallet = Wallet::create([
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'balance_kobo' => 0,
                'ledger_balance_kobo' => 0,
                'currency' => 'NGN',
                'status' => 'ACTIVE',
                'virtual_account_number' => $response['data']['accountNumber'],
                'virtual_account_bank' => '9Psb',
                'virtual_account_bank_code' => $response['data']['customerID'],
                'virtual_account_reference' => $response['data']['orderRef'],
            ]);

            DB::commit();

            return $wallet;

        }

        catch (\Throwable $e) {

            DB::rollBack();

            Log::error(

                'Wallet Creation Failed',

                [

                    'user_id' => $user->id,

                    'message' => $e->getMessage(),

                    'payload' => $payload ?? null,

                ]

            );

            throw $e;

        }

    }


    /**
     * Debit a wallet within an existing transaction.
     * Caller MUST wrap in DB::transaction().
     */
    public function debit(Wallet $wallet, int $amountKobo, string $correlationId, string $description): LedgerEntry
    {
        return DB::transaction(function () use ($wallet, $amountKobo, $correlationId, $description) {
            
            Log::info('Before debit');

            if (! $wallet->hasSufficientBalance($amountKobo)) {
                throw new \RuntimeException("Insufficient balance. Available: {$wallet->balance_kobo} kobo, Required: {$amountKobo} kobo.");
            }

            $wallet->lockForUpdate()->find($wallet->id); // pessimistic lock

            $newBalance = $wallet->balance_kobo - $amountKobo;

            $psb_amount = ($amountKobo / 100);

            $response = $this->digitalBankingService
                    ->debitWallet($wallet->virtual_account_number, $psb_amount, $correlationId, $description);

            Log::info('After debit');

            $wallet->update([
                'balance_kobo' => $newBalance,
                'ledger_balance_kobo' => $wallet->ledger_balance_kobo - $amountKobo,
            ]);

            Log::info('After wallet update');

            return LedgerEntry::create([
                'wallet_id' => $wallet->id,
                'entry_type' => 'DEBIT',
                'amount_kobo' => $amountKobo,
                'running_balance_kobo' => $newBalance,
                'correlation_id' => $correlationId,
                'description' => $description,
                'currency' => $wallet->currency,
            ]);
        });
    }

    /**
     * Credit a wallet within an existing transaction.
     * Caller MUST wrap in DB::transaction().
     */
    public function credit(Wallet $wallet, int $amountKobo, string $correlationId, string $description): LedgerEntry
    {
        $wallet->lockForUpdate()->find($wallet->id);

        $newBalance = $wallet->balance_kobo + $amountKobo;

        $wallet->update([
            'balance_kobo' => $newBalance,
            'ledger_balance_kobo' => $wallet->ledger_balance_kobo + $amountKobo,
        ]);

        return LedgerEntry::create([
            'wallet_id' => $wallet->id,
            'entry_type' => 'CREDIT',
            'amount_kobo' => $amountKobo,
            'running_balance_kobo' => $newBalance,
            'correlation_id' => $correlationId,
            'description' => $description,
            'currency' => $wallet->currency,
        ]);
    }

    /**
     * Reverse a previously debited amount (e.g., failed payment).
     */
    public function reverse(Wallet $wallet, int $amountKobo, string $correlationId, string $description): LedgerEntry
    {
        return $this->credit($wallet, $amountKobo, $correlationId, "REVERSAL: {$description}");
    }
}
