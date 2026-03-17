<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperStockOut
 */
class StockOut extends Model
{
    protected $table = 'stock_out';

    protected $fillable = [
        'business_id',
        'warehouse_id',
        'order_id',
        'customer_id',
        'created_by',
        'stock_out_no',
        'reference_no',
        'stock_out_type',
        'stock_out_date',
        'status',
        'subtotal',
        'total_amount',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'stock_out_date' => 'datetime',
            'subtotal' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockOutItem::class);
    }
}
