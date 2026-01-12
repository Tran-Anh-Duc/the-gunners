<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

//use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperStockOutItem
 */
class StockOutItem extends Model
{
    //use SoftDeletes;
    protected $table = "stock_out_items";

    protected $fillable = ['stock_out_id', 'product_id', 'quantity','price','subtotal','created_at','updated_at'];

    public function stockOut(): BelongsTo
    {
        return $this->belongsTo(StockOut::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
