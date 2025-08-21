<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends BaseModel
{
    protected $table = "permissions";
    protected $fillable = ['module_id','action_id','name','description'];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function action()
    {
        return $this->belongsTo(Action::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
