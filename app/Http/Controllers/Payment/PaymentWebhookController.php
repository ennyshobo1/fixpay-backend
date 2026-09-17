<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\Wallet\WalletCreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __construct(
        protected WalletCreditService $walletCreditService,
    ){
        $this->walletCreditService = $walletCreditService;
    }

    public function handle(Request $request)
    {
        try{

            Log::info('Webhook Received', [
                'payload' => $request->all()
            ]);

            $payload = $request->all();

            if (empty($payload['transaction_reference'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Transaction reference missing.'
                ], 400);
            }


            $this->walletCreditService->credit($payload['transaction_reference']);

            return response()->json([

                'status'=>true

            ]);

        }

        catch(\Throwable $e){

            return response()->json([

                'status'=>false,

                'message'=>$e->getMessage()

            ]);

        }

    }

}