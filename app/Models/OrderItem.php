<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

//use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperOrderItem
 */
class OrderItem extends Model
{
    //use SoftDeletes;
    protected $table = "order_items";

    protected $fillable = ['order_id', 'product_id', 'quantity','price','subtotal','created_at','updated_at'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
