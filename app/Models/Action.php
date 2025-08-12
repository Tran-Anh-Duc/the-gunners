<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
    protected $fillable = ['name', 'key', 'description'];

    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }
}
