<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentJournalEntry extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        'payment_id', 'step', 'status', 'metadata', 'actor',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(VtpassPayment::class, 'payment_id');
    }
}
