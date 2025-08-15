<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Action extends Model
{
    use SoftDeletes;
    protected $fillable = ['name', 'key', 'description','deleted_at'];

    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }
}
