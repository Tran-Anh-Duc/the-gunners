<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
//use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    //use SoftDeletes;
    protected $table = "products";

    protected $fillable = ['code', 'name', 'price','description','unit_id','status_id','created_at','updated_at'];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }
}
