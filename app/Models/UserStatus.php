<?php
namespace App\Models;

use \App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperUserStatus
 */
class UserStatus extends BaseModel
{
    use SoftDeletes;
    protected $table = "users_status";
    protected $fillable = ['name','description','slug'];



    public function usersStatus(): HasOne|Builder
    {
        return $this->hasOne(User::class);
    }

    public function users(): Builder|HasMany
    {
        return $this->hasMany(User::class, 'status_id');
    }

}
