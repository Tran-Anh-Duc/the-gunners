<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperAction
 */
class Action extends Model
{
    use SoftDeletes;
    protected $table = "actions";

    protected $fillable = ['name', 'key', 'description','deleted_at'];

    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }
}
