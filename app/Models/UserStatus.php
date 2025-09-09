<?php
namespace App\Models;

use \App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserStatus extends BaseModel
{
    use SoftDeletes;
    protected $table = "users_status";
    protected $fillable = ['name'];



    public function usersStatus()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

}
