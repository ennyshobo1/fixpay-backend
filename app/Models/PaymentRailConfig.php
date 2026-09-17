<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentRailConfig extends Model
{
    use HasUuids;

    protected $table = 'payment_rail_configs';

    protected $fillable = [
        'tenant_id', 'payment_method', 'processor_id',
        'priority', 'enabled', 'maintenance', 'config_json',
    ];

    protected $casts = [
        'config_json' => 'array',
        'enabled' => 'boolean',
        'maintenance' => 'boolean',
        'priority' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function feeSchedules(): HasMany
    {
        return $this->hasMany(ProcessorFeeSchedule::class, 'config_id');
    }
}
