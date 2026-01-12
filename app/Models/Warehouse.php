<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

//use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperWarehouse
 */
class Warehouse extends Model
{
    //use SoftDeletes;
    protected $table = "warehouses";

    protected $fillable = ['name', 'address', 'status_id','created_at','updated_at'];

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
