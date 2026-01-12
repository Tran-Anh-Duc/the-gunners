<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
//use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperCustomer
 */
class Customer extends Model
{
    //use SoftDeletes;
    protected $table = "customers";

    protected $fillable = ['name','phone','email','address','created_at','updated_at'];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
