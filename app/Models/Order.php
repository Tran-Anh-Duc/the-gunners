<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
//use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    //use SoftDeletes;
    protected $table = "orders";

    protected $fillable = ['customer_id', 'status_id', 'order_date','total_amount','note','created_at','updated_at'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function shipment()
    {
        return $this->hasOne(Shipment::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }
}
