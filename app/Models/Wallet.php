<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'tenant_id', 'balance_kobo', 'ledger_balance_kobo',
        'currency', 'status', 'virtual_account_number',
        'virtual_account_bank', 'virtual_account_bank_code', 'virtual_account_reference',
    ];

    protected $casts = [
        'balance_kobo' => 'integer',
        'ledger_balance_kobo' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function hasSufficientBalance(int $amountKobo): bool
    {
        return $this->balance_kobo >= $amountKobo;
    }
}
