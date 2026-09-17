<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookEndpoint extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id', 'url', 'events', 'signing_secret',
        'environment', 'active', 'failure_count', 'last_triggered_at',
    ];

    protected $hidden = ['signing_secret'];

    protected $casts = [
        'events' => 'array',
        'active' => 'boolean',
        'failure_count' => 'integer',
        'last_triggered_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
