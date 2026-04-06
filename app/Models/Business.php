<?php

namespace App\Models;

use App\Traits\HasNameSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Business dòng vai trò tenant/chu shop.
 *
 * Tat ca bang nghiệp vụ chinh deu map vao business_id,
 * giup app san sang cho mo hinh nhieu shop đúng chung hệ thống.
 */
class Business extends Model
{
    use HasNameSlug, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'name_slug',
        'phone',
        'email',
        'address',
        'plan_code',
        'status',
        'currency_code',
        'timezone',
    ];

    public function memberships(): HasMany
    {
        // Membership là lop quan he trung gian user <-> business.
        return $this->hasMany(BusinessUser::class);
    }

    public function users(): BelongsToMany
    {
        // Danh sách user thuộc business nay thong qua pivot business_users.
        return $this->belongsToMany(User::class, 'business_users')
            ->withPivot(['role', 'status', 'is_owner', 'joined_at'])
            ->withTimestamps();
    }

    public function modules(): HasMany
    {
        // Cac module dang được bat cho business để UI/API mo khoa tinh nang.
        return $this->hasMany(BusinessModule::class);
    }

    public function units(): HasMany
    {
        // Master data đơn vì tinh của rieng business nay.
        return $this->hasMany(Unit::class);
    }

    public function warehouses(): HasMany
    {
        // Tat ca kho thuộc business.
        return $this->hasMany(Warehouse::class);
    }

    public function customers(): HasMany
    {
        // Tap khách hàng của business nay.
        return $this->hasMany(Customer::class);
    }

    public function suppliers(): HasMany
    {
        // Tap nhà cùng cấp của business nay.
        return $this->hasMany(Supplier::class);
    }

    public function products(): HasMany
    {
        // Catalog sản phẩm của business.
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        // Don hang ban ra trong business.
        return $this->hasMany(Order::class);
    }

    public function stockIns(): HasMany
    {
        // Chứng từ nhập kho.
        return $this->hasMany(StockIn::class);
    }

    public function stockOuts(): HasMany
    {
        // Chứng từ xuất kho.
        return $this->hasMany(StockOut::class);
    }

    public function stockAdjustments(): HasMany
    {
        // Chứng từ kiểm kho/điều chỉnh tồn.
        return $this->hasMany(StockAdjustment::class);
    }

    public function payments(): HasMany
    {
        // Phieu thu/chỉ của business.
        return $this->hasMany(Payment::class);
    }

    public function inventoryMovements(): HasMany
    {
        // Ledger tồn kho - source of truth.
        return $this->hasMany(InventoryMovement::class);
    }

    public function currentStocks(): HasMany
    {
        // Bang tổng hợp ton hiện tại để doc nhanh.
        return $this->hasMany(CurrentStock::class);
    }
}
