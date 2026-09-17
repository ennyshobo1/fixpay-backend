<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantKybSubmission extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id', 'business_name', 'cac_number', 'tin_number', 'business_type',
        'business_address', 'website_url', 'directors', 'document_urls',
        'status', 'review_notes', 'reviewed_by', 'submitted_at', 'reviewed_at',
    ];

    protected $casts = [
        'directors' => 'array',
        'document_urls' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
