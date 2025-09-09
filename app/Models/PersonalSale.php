<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class PersonalSale extends Model
{

    protected $table = "personal_sales";
    protected $fillable = ['distributor_id', 'price','month'];

    public function distributor()
    {
        return $this->belongsTo(Distributor::class, 'distributor_id');
    }



}
