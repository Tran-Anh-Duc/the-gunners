<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

//use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperOpeningInventory
 */
class OpeningInventory extends Model
{
    //use SoftDeletes;
    protected $table = "opening_inventories";

    protected $fillable = ['warehouse_id', 'product_id', 'opening_quantity','opening_unit_price','opening_total_value','period','created_at','updated_at'];
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
