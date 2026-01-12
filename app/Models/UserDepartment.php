<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperUserDepartment
 */
class UserDepartment extends BaseModel
{
    use HasFactory;

    protected $table = 'user_department';

    protected $fillable = [
        'user_id',
        'department_id',
        'is_main',
        'position',
        'assigned_at',
        'ended_at',
    ];

    public $timestamps = false;
}
