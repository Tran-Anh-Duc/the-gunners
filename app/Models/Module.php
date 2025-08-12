<?php
namespace App\Models;

use \App\Models\BaseModel;

class Module extends BaseModel
{
    protected $fillable = ['name','description','icon','order'];

    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }
}
