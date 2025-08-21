<?php

namespace App\Http\Controllers\Api;

use App\Models\Role;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Lấy danh sách tất cả user kèm role và permission.
     */
    public function index()
    {
        $users = User::with(['roles.permissions', 'permissions'])->get();
        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }




}
