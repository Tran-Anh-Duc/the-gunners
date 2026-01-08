<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

//use Illuminate\Database\Eloquent\SoftDeletes;

class StockInItem extends Model
{
    //use SoftDeletes;
    protected $table = "stock_in_items";

    protected $fillable = ['stock_in_id', 'product_id', 'quantity','price','subtotal','created_at','updated_at'];

    public function stockIn(): BelongsTo
    {
        return $this->belongsTo(StockIn::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
