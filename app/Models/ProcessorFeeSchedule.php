<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessorFeeSchedule extends Model
{
    use HasUuids;

    protected $fillable = [
        'config_id', 'min_amount_kobo', 'max_amount_kobo',
        'percentage_fee', 'flat_fee_kobo', 'cap_kobo',
        'effective_from', 'effective_to',
    ];

    protected $casts = [
        'min_amount_kobo' => 'integer',
        'max_amount_kobo' => 'integer',
        'flat_fee_kobo' => 'integer',
        'cap_kobo' => 'integer',
        'percentage_fee' => 'decimal:4',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
    ];

    public function config(): BelongsTo
    {
        return $this->belongsTo(PaymentRailConfig::class, 'config_id');
    }

    public function calculateFee(int $amountKobo): int
    {
        // Use BCMath to compute precise percentage fee (scale 4)
        $percentFee = bcmul((string) $amountKobo, (string) $this->percentage_fee, 4);
        
        // Round to nearest integer using pure BCMath (add 0.5 and truncate to scale 0)
        $roundedPercentFee = bcadd($percentFee, '0.5', 0);

        // Add the flat fee in kobo using BCMath
        $totalFee = (int) bcadd($roundedPercentFee, (string) $this->flat_fee_kobo, 0);

        if ($this->cap_kobo !== null && $totalFee > $this->cap_kobo) {
            return $this->cap_kobo;
        }

        return $totalFee;
    }
}
