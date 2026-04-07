<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessSequence extends Model
{
    protected $fillable = [
        'business_id',
        'scope',
        'prefix',
        'current_value',
    ];

    protected function casts(): array
    {
        return [
            'current_value' => 'integer',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
