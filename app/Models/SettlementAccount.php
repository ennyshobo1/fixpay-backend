<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementAccount extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id', 'bank_code', 'bank_name',
        'account_number', 'account_name', 'currency', 'verified',
    ];

    protected $casts = [
        'verified' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
