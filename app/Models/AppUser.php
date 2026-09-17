<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class AppUser extends Authenticatable
{
    use HasUuids, SoftDeletes, HasApiTokens, HasRoles, HasFactory;

    protected $table = 'app_users';

    protected $fillable = [
        'tenant_id', 'phone', 'email', 'first_name', 'last_name',
        'password_hash', 'pin_hash', 'kyc_status', 'tier', 'status',
        'email_verified_at', 'phone_verified_at',
    ];

    protected $hidden = [
        'password_hash', 'pin_hash',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'tier' => 'integer',
    ];

    // Laravel auth expects 'password' column name
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'user_id');
    }

    public function kycVerifications(): HasMany
    {
        return $this->hasMany(KycVerification::class, 'user_id');
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class, 'user_id');
    }

    public function mandates(): HasMany
    {
        return $this->hasMany(NibssMandate::class, 'user_id');
    }

    public function isKycVerified(): bool
    {
        return $this->kyc_status === 'VERIFIED';
    }
}
