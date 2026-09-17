<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transfer extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'wallet_id', 'tenant_id', 'transfer_reference', 'idempotency_key',
        'transfer_type', 'amount_kobo', 'fee_kobo', 'narration',
        'account_number', 'bank_code', 'bank_name', 'account_name',
        'paystack_transfer_code', 'paystack_recipient_code',
        'recipient_user_id', 'recipient_wallet_id',
        'status', 'provider_response', 'completed_at', 'failed_at', 'failure_reason',
    ];

    protected $casts = [
        'provider_response' => 'array',
        'amount_kobo' => 'integer',
        'fee_kobo' => 'integer',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
