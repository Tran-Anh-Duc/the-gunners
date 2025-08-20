<?php
namespace App\Models;

use \App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends BaseModel
{
    use SoftDeletes;
    protected $fillable = ['name','description','icon','order','code','deleted_at'];

    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }
}
