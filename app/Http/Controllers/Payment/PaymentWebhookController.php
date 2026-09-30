<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\Wallet\WalletCreditService;
use Illuminate\Http\Request;
use App\Jobs\ProcessVtpassPaymentJob;
use App\Models\VtpassPayment;
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

            $payment_id = $request->query('vtpayment_id');

            // if (empty($payload['transaction_reference'])) {
            //     return response()->json([
            //         'status' => false,
            //         'message' => 'Transaction reference missing.'
            //     ], 400);
            // }

            $payment_id = $request->query('vtpayment_id');

            Log::info('payment_id', ['payment_id' => $payment_id]);

            if($payment_id) {

                $vtPayment = VtpassPayment::where('id', $payment_id)->firstorfail();
                
                // if($vtPayment) {
                //     $vtPayment->update(['payment_status' => 'SUCCESS']);
                // }

                // $amount = $payload['amount'] ?? 0;
                // $vtpayment_amount = $vtPayment->amount_kobo ?? 0;

                // if(($amount * 100) != $vtpayment_amount) {
                //     Log::warning('Payment amount mismatch', [
                //         'vtpayment_id' => $payment_id,
                //         'expected_amount' => $vtpayment_amount,
                //         'received_amount' => $amount,
                //     ]);
                // }

                $payment = ProcessVtpassPaymentJob::dispatchSync($payment_id);

                Log::info ('payment response', ['payment' => $payment]);
            }

            else
            {
                $payment = $this->walletCreditService->credit($payload['transaction_reference']);
            }

            return response()->json([
                'status'=>true,
                'message'=>'Webhook processed successfully.',
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