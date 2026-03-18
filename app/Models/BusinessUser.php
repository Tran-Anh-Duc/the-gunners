<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Membership gắn user với business.
 *
 * Role trong MVP được dat tại đây thay vì đúng he permission sau,
 * giup để cođể va để mở rộng nhieu business cho một user.
 */
class BusinessUser extends Model
{
    protected $fillable = [
        'business_id',
        'user_id',
        'role',
        'status',
        'is_owner',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'is_owner' => 'boolean',
            'joined_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        // Membership nay thuộc business nào.
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        // Membership nay gắn với user nào.
        return $this->belongsTo(User::class);
    }
}
