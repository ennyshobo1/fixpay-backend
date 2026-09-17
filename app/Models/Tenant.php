<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'email', 'phone', 'status', 'plan', 'kyb_status',
        'branding', 'feature_flags', 'whitelabel_config',
        'go_live_requested_at', 'activated_at', 'suspended_at',
    ];

    protected $casts = [
        'branding' => 'array',
        'feature_flags' => 'array',
        'whitelabel_config' => 'array',
        'go_live_requested_at' => 'datetime',
        'activated_at' => 'datetime',
        'suspended_at' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(AppUser::class);
    }

    public function kybSubmission(): HasOne
    {
        return $this->hasOne(TenantKybSubmission::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function webhookEndpoints(): HasMany
    {
        return $this->hasMany(WebhookEndpoint::class);
    }

    public function ipWhitelistRules(): HasMany
    {
        return $this->hasMany(IpWhitelistRule::class);
    }

    public function settlementAccount(): HasOne
    {
        return $this->hasOne(SettlementAccount::class);
    }

    public function paymentRailConfigs(): HasMany
    {
        return $this->hasMany(PaymentRailConfig::class);
    }

    public function isSandbox(): bool
    {
        return $this->status === 'SANDBOX';
    }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }
}
