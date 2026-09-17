<?php

namespace App\Console\Commands;

use App\Models\VtpassPayment;
use App\Services\Wallet\WalletService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TimeoutStalePaymentsCommand extends Command
{
    protected $signature = 'payments:timeout-stale
                            {--dry-run : Print stale payments without reversing}';

    protected $description = 'Mark PENDING VTPass payments as FAILED and reverse wallet debits';

    public function __construct(private readonly WalletService $wallet)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $ttlSeconds = (int) env('PAYMENT_TIMEOUT_SECONDS', 300);
        $cutoff = now()->subSeconds($ttlSeconds);

        $stale = VtpassPayment::where('status', 'PENDING')
            ->where('created_at', '<', $cutoff)
            ->with('user.wallet')
            ->get();

        if ($stale->isEmpty()) {
            $this->info('No stale payments found.');
            return self::SUCCESS;
        }

        $this->info("Found {$stale->count()} stale payment(s).");

        if ($this->option('dry-run')) {
            $stale->each(fn ($p) => $this->line("  [{$p->id}] {$p->service_id} {$p->amount_kobo}k — {$p->created_at}"));
            return self::SUCCESS;
        }

        $reversed = 0;
        foreach ($stale as $payment) {
            try {
                DB::transaction(function () use ($payment, &$reversed) {
                    $payment->update(['status' => 'FAILED']);

                    $wallet = $payment->user?->wallet;
                    if ($wallet && $payment->amount_kobo > 0) {
                        $this->wallet->reverse(
                            $wallet,
                            $payment->amount_kobo,
                            "TIMEOUT_REVERSAL:{$payment->id}",
                            'Payment timed out — auto-reversed'
                        );
                    }

                    $reversed++;
                    Log::info("Timed out payment {$payment->id} and reversed {$payment->amount_kobo} kobo.");
                });
            } catch (\Throwable $e) {
                Log::error("Failed to timeout payment {$payment->id}: {$e->getMessage()}");
                $this->error("  Error on [{$payment->id}]: {$e->getMessage()}");
            }
        }

        $this->info("Done. Reversed {$reversed}/{$stale->count()} payments.");
        return self::SUCCESS;
    }
}
