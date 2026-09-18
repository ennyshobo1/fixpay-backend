<?php

namespace App\Jobs;

use App\Models\VtpassPayment;
use App\Services\Payment\VtpassService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessVtpassPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public string $paymentId) {}

    public function handle(VtpassService $vtpass): void
    {
        $payment = VtpassPayment::find($this->paymentId);

        if (! $payment) {
            return;
        }

        if (in_array($payment->payment_status, ['COMPLETED', 'FAILED'])) {
            return;
        }

        try {
            $vtpass->submit($payment);
        } catch (Throwable $e) {
            Log::error('VTPass payment job failed', [
                'payment_id' => $payment->id,
                'payment_reference' => $payment->payment_reference,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('VTPass payment job permanently failed', [
            'payment_id' => $this->paymentId,
            'error' => $e->getMessage(),
        ]);
    }
}