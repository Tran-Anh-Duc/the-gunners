<?php

namespace App\Http\Controllers\Api;

use App\Models\Role;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Transformers\UserTransform;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;
    protected $userTransform;
    /**
     * Lấy danh sách tất cả user kèm role và permission.
     */

    public function __construct(UserTransform $userTransform)
    {
        $this->userTransform = $userTransform;
    }

    public function index()
    {
        $users = User::with(
            [
                'department:id,name',
                'status:id,name'
            ]
        )->get();

        $dataTran = $this->transformData($users,$this->userTransform)['data'];
        return response()->json([
            'status' => 'success',
            'data' => $dataTran
        ]);
    }


    public function show($id)
    {
        $users = User::with(
           [
               'department:id,name',
               'status:id,name'
           ]
        )
        ->findOrFail($id);
        $dataTran = $this->transformData($users,$this->userTransform)['data'];
        return response()->json([
            'status' => 'success',
            'data' => $dataTran
        ]);
    }

    



}
