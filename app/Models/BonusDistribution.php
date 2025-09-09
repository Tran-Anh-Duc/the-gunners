<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BonusDistribution extends Model
{

    protected $table = "bonus_distributions";
    protected $fillable = ['distributor_id', 'month','year','amount'];

    public function personalSales()
    {
        return $this->hasMany(PersonalSale::class, 'distributor_id');
    }

    public static function getAllWithTotalSales()
    {
        return Distributor::withSum('personalSales','price')->get()->toArray();
    }


}
