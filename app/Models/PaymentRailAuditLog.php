<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRailAuditLog extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'payment_rail_config_id',
        'changed_by',
        'action',
        'before_json',
        'after_json',
        'ip_address',
    ];

    protected $casts = [
        'before_json' => 'array',
        'after_json'  => 'array',
    ];

    public function railConfig(): BelongsTo
    {
        return $this->belongsTo(PaymentRailConfig::class, 'payment_rail_config_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'changed_by');
    }
}
