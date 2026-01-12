<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

//use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperStockOut
 */
class StockOut extends Model
{
    //use SoftDeletes;
    protected $table = "stock_out";

    protected $fillable = ['warehouse_id', 'related_order_id', 'created_by','date','total_amount','status','note','created_at','updated_at'];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'related_order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(StockOutItem::class);
    }
}
