<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerEntry extends Model
{
    use HasUuids;

    const UPDATED_AT = null; // append-only — no updated_at

    protected $fillable = [
        'wallet_id', 'entry_type', 'amount_kobo',
        'running_balance_kobo', 'correlation_id', 'description', 'currency',
    ];

    protected $casts = [
        'amount_kobo' => 'integer',
        'running_balance_kobo' => 'integer',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    // Block updates at model level as well
    public function save(array $options = []): bool
    {
        if (! $this->wasRecentlyCreated && $this->exists) {
            throw new \RuntimeException('Ledger entries are immutable.');
        }

        return parent::save($options);
    }
}
