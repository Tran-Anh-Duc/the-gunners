<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

//use Illuminate\Database\Eloquent\SoftDeletes;

class Shipment extends Model
{
    //use SoftDeletes;
    protected $table = "shipments";

    protected $fillable = ['order_id', 'shipper_id', 'vehicle_id','status_id','delivery_date','note','created_at','updated_at'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function shipper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shipper_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }
}
