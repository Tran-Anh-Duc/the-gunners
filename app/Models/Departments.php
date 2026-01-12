<?php
namespace App\Models;

use \App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperDepartments
 */
class Departments extends BaseModel
{
    use SoftDeletes;
    protected $table = "departments";
    protected $fillable = ['name','description'];

    public function users()
    {
        return $this->hasMany(User::class, 'department_id', 'id');
    }

    //quan he 1-n 
    public function user()
    {
        return $this->belongsToMany(User::class, 'user_department')
                    ->withPivot(['assigned_at', 'ended_at', 'is_main', 'position'])
                    ->withTimestamps();
    }


}
