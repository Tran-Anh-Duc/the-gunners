<?php
namespace App\Models;

use \App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Departments extends BaseModel
{
    use SoftDeletes;
    protected $table = "departments";
    protected $fillable = ['name'];

    public function users()
    {
        return $this->hasMany(User::class, 'department_id', 'id');
    }


}
