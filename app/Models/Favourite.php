<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favourite extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'type',
        'service_id',
        'service_name',
        'counterparty_name',
        'description',
        'amount_kobo',
        'transaction_reference',
    ];

    protected $casts = [
        'amount_kobo' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }
}
