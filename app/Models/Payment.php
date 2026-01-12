<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

//use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperPayment
 */
class Payment extends Model
{
    //use SoftDeletes;
    protected $table = "payments";

    protected $fillable = ['order_id', 'status_id', 'payment_method','amount','paid_date','created_at','updated_at'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'warehouse_user')
            ->withPivot('role_id');
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }
}
