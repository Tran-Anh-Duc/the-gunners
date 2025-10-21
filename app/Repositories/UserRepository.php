<?php

namespace App\Repositories;


use App\Models\Action;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDepartment;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use function Illuminate\Cache\table;
use function Termwind\ValueObjects\pr;


class UserRepository extends BaseRepository
{
    //use CaculatePriceWareHouseTrait;

    public function __construct(User $user)
    {
        return $this->model = $user;
    }

    public function getModel()
    {
        return User::class;
    }

    /* Cập nhật bộ phận của nhân viên */

    public function update_user_department($data,$id)
    {
        /*kiem tra user*/
        $user = User::find($id);

        if (!$user){
            $dataResponse = [
                'status' => 422,
            ];
            return  $dataResponse;
        }

        /*kiem tra neu action_type = 2 , update */
        if (!empty($data) and $data != ''){

            if ($data['action_type'] != 2){
                $dataResponse = [
                    'status' => 422,
                ];
                return  $dataResponse;
            }

            if ($data['department_id'] == '' || $data['department_id'] == null){
                $dataResponse = [
                    'status' => 422,
                ];
                return  $dataResponse;
            }

            /*kiem tra du lieu trong bang user_department */

            //$findData = UserDepartment::query()->where('id',)

            $dataUpdate = [];





        }else{
            $dataResponse = [
                'status' => 422,
            ];
        }

        return $dataResponse;

    }

    public function create_user_department($data,$id)
    {
        /*kiem tra user*/
        $user = User::find($id);

        if (!$user){
            $dataResponse = [
                'status' => 422,
            ];
            return  $dataResponse;
        }


        /*kiem tra neu action_type = 1 , them moi */
        if (!empty($data) and $data != ''){

            if ($data['action_type'] != 1){
                $dataResponse = [
                    'status' => 422,
                ];
                return  $dataResponse;
            }

            $dataCreate = [
                'user_id' => $id,
                'department_id' => $data['department_id'] ?? '',
                'is_main' => $data['is_main'] ?? 0,
                'position' => $data['position'],
                'assigned_at' => $data['assigned_at'],
            ];

            $resultData = UserDepartment::create($dataCreate);

            $dataResponse = [
                'status' => 200,
                'data' => $resultData,
            ];

        }else{
            $dataResponse = [
                'status' => 422,
            ];
        }

        return  $dataResponse;
    }




}
