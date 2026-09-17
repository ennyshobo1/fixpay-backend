<?php

namespace App\Services\Wallet;

use App\Models\Payment;
use App\Services\Payment\PayfixyPaymentService;
use App\Models\AppUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletFundingService
{
    public function __construct(
        protected PayfixyPaymentService $payfixy
    )
    {
        $this->payfixy = $payfixy;
    }

    public function initialize(

        AppUser $user,

        float $amount

    ): array {

        DB::beginTransaction();

        try {

            if (!$user->wallet) {

                throw new \Exception(
                    'Wallet not found.'
                );

            }

            $response = $this->payfixy

                ->initializePayment(

                    $user->email,

                    $amount,

                    config('services.payfixy.callback_url')

                );

            Payment::create([

                'user_id'=>$user->id,

                'reference'=>$response['data']['reference'],

                'access_code'=>$response['data']['access_code'],

                'payment_url'=>$response['data']['payment_url'],

                'amount'=>$amount,

                'status'=>'PENDING'

            ]);

            DB::commit();

            return $response['data'];

        }

        catch(\Throwable $e){

            DB::rollBack();

            Log::error($e->getMessage());

            throw $e;

        }

    }

}