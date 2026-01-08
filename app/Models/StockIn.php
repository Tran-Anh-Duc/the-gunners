<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

//use Illuminate\Database\Eloquent\SoftDeletes;

class StockIn extends Model
{
    //use SoftDeletes;
    protected $table = "stock_in";

    protected $fillable = ['warehouse_id', 'supplier_id', 'created_by','date','total_amount','status','note','created_at','updated_at'];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(StockInItem::class);
    }
}
