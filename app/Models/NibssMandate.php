<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NibssMandate extends Model
{
    use HasUuids;

    protected $table = 'nibss_mandates';

    protected $fillable = [
        'user_id', 'mandate_reference', 'description', 'amount_kobo',
        'frequency', 'beneficiary_bank_code', 'beneficiary_account_number',
        'authorization_url', 'provider_reference', 'status',
        'start_date', 'end_date',
    ];

    protected $casts = [
        'amount_kobo' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }
}
