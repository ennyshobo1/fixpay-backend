<?php

namespace App\Services\Payment;

use App\Models\PaymentRailConfig;
use App\Models\ProcessorFeeSchedule;
use Illuminate\Support\Carbon;

class PaymentRailService
{
    /**
     * Get the active payment rail config for a given method and tenant.
     */
    public function getActiveRail(string $paymentMethod, ?string $tenantId = null): ?PaymentRailConfig
    {
        return PaymentRailConfig::where('payment_method', $paymentMethod)
            ->where('enabled', true)
            ->where('maintenance', false)
            ->where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)
                  ->orWhereNull('tenant_id');
            })
            ->orderByDesc('priority')
            ->first();
    }

    /**
     * Calculate fee for a given rail config and amount.
     */
    public function calculateFee(PaymentRailConfig $config, int $amountKobo): int
    {
        $schedule = ProcessorFeeSchedule::where('config_id', $config->id)
            ->where('min_amount_kobo', '<=', $amountKobo)
            ->where(function ($q) use ($amountKobo) {
                $q->where('max_amount_kobo', '>=', $amountKobo)
                  ->orWhereNull('max_amount_kobo');
            })
            ->where(function ($q) {
                $q->where('effective_from', '<=', now())
                  ->orWhereNull('effective_from');
            })
            ->where(function ($q) {
                $q->where('effective_to', '>=', now())
                  ->orWhereNull('effective_to');
            })
            ->first();

        if (! $schedule) {
            return 0;
        }

        return $schedule->calculateFee($amountKobo);
    }
}
