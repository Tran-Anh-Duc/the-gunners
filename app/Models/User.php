<?php

namespace App\Models;

use App\Traits\HasNameSlug;
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
/**
 * User hệ thống.
 *
 * Bảng `users` không lưu role theo business.
 * Quyền và vai trò được suy ra từ `business_users` để hỗ trợ mô hình multi-business.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasNameSlug, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'name_slug',
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
        // Danh sách business mà user đang tham gia thông qua pivot `business_users`.
        return $this->belongsToMany(Business::class, 'business_users')
            ->withPivot(['role', 'status', 'is_owner', 'joined_at'])
            ->withTimestamps();
    }

    public function businessMemberships(): HasMany
    {
        // Toàn bộ membership của user trên các business.
        return $this->hasMany(BusinessUser::class);
    }

    public function activeBusinessMemberships(): HasMany
    {
        // Chỉ lấy membership đang active để dùng cho login, JWT và phân quyền.
        return $this->businessMemberships()->where('status', 'active');
    }

    public function hasRole(string $roleName, ?int $businessId = null): bool
    {
        // Kiểm tra role trong phạm vi business cụ thể hoặc membership active đầu tiên.
        $membership = $this->resolveScopedMembership($businessId);

        return $membership?->role === $roleName;
    }

    public function hasPermission(string $permissionName, ?int $businessId = null): bool
    {
        /**
         * MVP chưa dùng hệ permission DB đầy đủ.
         *
         * Tạm thời map permission cứng dựa trên role membership
         * để dễ maintain và sẵn sàng thay bằng RBAC sau này.
         */
        $membership = $this->resolveScopedMembership($businessId);

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
                'customers.view',
                'customers.create',
                'customers.update',
                'customers.delete',
                'suppliers.view',
                'suppliers.create',
                'suppliers.update',
                'suppliers.delete',
                'warehouse_documents.view',
            ],
            'staff' => [
                'products.view',
                'products.create',
                'products.update',
                'inventory.view',
                'inventory.create',
                'customers.view',
                'customers.create',
                'customers.update',
                'suppliers.view',
            ],
        ];

        return in_array($permissionName, $permissionMap[$membership->role] ?? [], true);
    }

    protected function scopedMembershipQuery(?int $businessId = null)
    {
        // Helper nội bộ cho `hasRole()` và `hasPermission()` để lấy membership đúng scope.
        $query = $this->activeBusinessMemberships();

        if ($businessId !== null) {
            return $query->where('business_id', $businessId);
        }

        return $query->orderByDesc('is_owner')->orderBy('id');
    }

    protected function resolveScopedMembership(?int $businessId = null): ?BusinessUser
    {
        if (app()->bound('jwt_active_membership')) {
            /** @var BusinessUser $membership */
            $membership = app('jwt_active_membership');

            if ($membership->user_id === $this->id && ($businessId === null || (int) $membership->business_id === $businessId)) {
                return $membership;
            }
        }

        if ($this->relationLoaded('activeBusinessMemberships')) {
            /** @var \Illuminate\Database\Eloquent\Collection<int, BusinessUser> $memberships */
            $memberships = $this->getRelation('activeBusinessMemberships');

            if ($businessId !== null) {
                return $memberships->firstWhere('business_id', $businessId);
            }

            return $memberships->first();
        }

        return $this->scopedMembershipQuery($businessId)->first();
    }
}
