<?php

namespace App\Services\Wallet;

use App\Models\Payment;
use App\Models\Wallet;
use App\Services\Payment\PayfixyPaymentService;
use App\Services\Wallet\DigitalBankingService;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletCreditService
{
    public function __construct(
        protected PayfixyPaymentService $payfixyPaymentService,
        protected DigitalBankingService $digitalBankingService
    ) {}

    /**
     * Verify payment, credit digital wallet and update local wallet.
     */
    public function credit(string $transactionReference): void
    {
        DB::beginTransaction();

        try {
            /**
             * Verify payment with Payfixy
             */
            $verification = $this->payfixyPaymentService
                ->verifyPayment($transactionReference);
            /**
             * Ensure payment was successful
             */
            if (
                !$verification['status'] ||
                $verification['code'] !== '00'
            ) {
                throw new \Exception(
                    $verification['message'] ?? 'Payment verification failed.'
                );
            }

            /**
             * Find local payment
             */
            $payment = Payment::where(
                'reference',
                $verification['data']['reference']
            )->lockForUpdate()->first();

            if (!$payment) {
                throw new \Exception('Payment not found.');
            }

            /**
             * Idempotency
             */
            if ($payment->status === 'SUCCESS') {

                DB::commit();

                return;
            }

            /**
             * User Wallet
             */
            $wallet = Wallet::where(
                'user_id',
                $payment->user_id
            )->lockForUpdate()->first();

            if (!$wallet) {
                throw new \Exception('Wallet not found.');
            }

            /**
             * Credit Digital Bank Wallet
             */
            $credit = $this->digitalBankingService
                ->creditWallet(

                    accountNumber: $wallet->virtual_account_number,

                    amount: $payment->amount,

                    transactionId: $verification['data']['transaction']['reference'],

                    narration: 'Wallet Funding'

                );

            if (
                strtolower($credit['status']) !== 'success'
            ) {

                throw new \Exception(
                    $credit['message']
                );

            }

            /**
             * Update wallet balance
             */
            $wallet->increment(
                'balance_kobo',
                $payment->amount * 100
            );

            /**
             * Mark payment successful
             */
            $payment->update([

                'status' => 'SUCCESS',

                'transaction_reference' =>
                    $verification['data']['transaction']['reference'],

                'gateway_reference' =>
                    $credit['data']['reference'],

                'response' => $verification

            ]);

            /**
             * Wallet transaction
             */
            WalletTransaction::create([

                'wallet_id' => $wallet->id,

                'user_id' => $payment->user_id,

                'type' => 'CREDIT',

                'amount' => $payment->amount,

                'reference' =>
                    $verification['data']['transaction']['reference'],

                'description' => 'Wallet Funding',

                'status' => 'SUCCESS'

            ]);

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error(
                'Wallet Funding Failed',
                [
                    'transaction_reference' => $transactionReference,
                    'message' => $e->getMessage(),
                ]
            );

            throw $e;
        }
    }
}