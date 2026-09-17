<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycVerification extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'type', 'identifier', 'provider',
        'provider_reference', 'verification_status',
        'response_json', 'failure_reason', 'verified_at',
    ];

    protected $casts = [
        'response_json' => 'array',
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function isVerified(): bool
    {
        return $this->verification_status === 'VERIFIED';
    }
}
