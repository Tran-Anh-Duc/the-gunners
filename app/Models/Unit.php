<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
//use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    //use SoftDeletes;
    protected $table = "units";

    protected $fillable = ['name', 'code','created_at','updated_at'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
