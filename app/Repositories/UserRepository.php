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
        /*kiem tra tinh ton  cua ban ghi*/
        $dataRecord = UserDepartment::find($id);
        if (!$dataRecord){
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

            /*kiem tra du lieu trong bang user_department $dataRecord */
            $fields = [
                'assigned_at',
                'is_main',
                'ended_at',
            ];

            $dataUpdate = [];
            foreach ($fields as $field){
                if (isset($data[$field]) and $data[$field] != '' and $data[$field] != null){
                    $dataUpdate[$field] = $data[$field];
                }
            }

            /*truong hop chon bo phan lam chinh is_main = 1 => se up tat ca cac bo phan cua user
                do ve 0 => roi moi update bo phan hien tai lam bo phan chinh
            */
            if (!empty($dataUpdate['is_main']) && $dataUpdate['is_main'] == 1) {
                UserDepartment::query()
                    ->where('user_id', $dataRecord->user_id)
                    ->where('id', '<>', $id)
                    ->update(['is_main' => 0]);
            }

            $dataRecord->update($dataUpdate);

            $dataResponse = [
                'status' => 200,
                'data' => $dataRecord,
            ];

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
