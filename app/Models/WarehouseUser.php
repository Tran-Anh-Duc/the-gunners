<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
//use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperWarehouseUser
 */
class WarehouseUser extends Model
{
    //use SoftDeletes;
    protected $table = "warehouse_user";

    protected $fillable = ['warehouse_id', 'user_id', 'role_id','created_at','updated_at'];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
