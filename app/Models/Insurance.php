<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Insurance extends Model
{
    protected $guarded = []; // Allow mass assignment for all attributes
    protected $table = 'insurances'; // Specify the table name if it's not the plural form of the model name

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class);
    }
}
