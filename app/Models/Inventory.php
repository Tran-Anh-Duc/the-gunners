<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

//use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperInventory
 */
class Inventory extends Model
{
    //use SoftDeletes;
    protected $table = "inventories";

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'inventory_date',
        'opening_quantity',
        'import_quantity',
        'export_quantity',
        'closing_quantity',
        'import_value',
        'export_value',
        'closing_value',
        'average_quantity',
        'average_cost',
        'created_at',
        'updated_at'
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
