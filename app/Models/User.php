<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @mixin IdeHelperUser
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class, 'business_users')
            ->withPivot(['role', 'status', 'is_owner', 'joined_at'])
            ->withTimestamps();
    }

    public function businessMemberships(): HasMany
    {
        return $this->hasMany(BusinessUser::class);
    }

    public function activeBusinessMemberships(): HasMany
    {
        return $this->businessMemberships()->where('status', 'active');
    }

    public function hasRole(string $roleName, ?int $businessId = null): bool
    {
        return $this->scopedMembershipQuery($businessId)
            ->where('role', $roleName)
            ->exists();
    }

    public function hasPermission(string $permissionName, ?int $businessId = null): bool
    {
        $membership = $this->scopedMembershipQuery($businessId)->first();

        if (! $membership) {
            return false;
        }

        if ($membership->is_owner) {
            return true;
        }

        $permissionMap = [
            'manager' => [
                'users.view',
                'users.create',
                'users.update',
                'products.view',
                'products.create',
                'products.update',
                'products.delete',
                'inventory.view',
                'inventory.create',
                'inventory.update',
                'inventory.delete',
                'orders.view',
                'orders.create',
                'orders.update',
                'customers.view',
                'customers.create',
                'customers.update',
                'customers.delete',
                'suppliers.view',
                'suppliers.create',
                'suppliers.update',
                'suppliers.delete',
                'payments.view',
                'payments.create',
                'payments.update',
            ],
            'staff' => [
                'products.view',
                'products.create',
                'products.update',
                'inventory.view',
                'inventory.create',
                'orders.view',
                'orders.create',
                'orders.update',
                'customers.view',
                'customers.create',
                'customers.update',
                'suppliers.view',
                'payments.view',
                'payments.create',
            ],
        ];

        return in_array($permissionName, $permissionMap[$membership->role] ?? [], true);
    }

    protected function scopedMembershipQuery(?int $businessId = null)
    {
        $query = $this->activeBusinessMemberships();

        if ($businessId !== null) {
            return $query->where('business_id', $businessId);
        }

        return $query->orderByDesc('is_owner')->orderBy('id');
    }
}
