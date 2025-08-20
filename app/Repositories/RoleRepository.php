<?php

namespace App\Repositories;


use App\Models\Action;
use App\Models\Role;
use App\Repositories\BaseRepository;



class RoleRepository extends BaseRepository
{
    //use CaculatePriceWareHouseTrait;

    public function __construct(Role $role)
    {
        return $this->model = $role;
    }

    public function getModel()
    {
        return Role::class;
    }

    public function getList()
    {
        $getAll = Role::query();
        $dataReturnMess = 'Tìm dữ liệu thành công';
        $dataResponse = [
            'status' => 200,
            'message' => $dataReturnMess,
            'data' => $getAll,
        ];
        return $dataResponse;
    }

    public function storeData($data)
    {
        if (!empty($data) and $data !=''){
            $dataCreate = [
                'name' => $data['name'],
                'code' => $data['code']?? '',
                'title' => $data['title']?? '',
            ];

            $getData = Role::query()->create($dataCreate);

            $dataReturnMess = 'Tìm dữ liệu thành công';
            $dataResponse = [
                'status' => 200,
                'message' => $dataReturnMess,
                'data' => $getData,
            ];
        }else{
            $dataReturnMess = 'Không tìm thấy dữ liệu';
            $dataResponse = [
                'status' => 422,
                'message' => $dataReturnMess
            ];
        }

        return $dataResponse;
    }


    public function ShowData($id)
    {
           if (!empty($id) and $id != ''){

               $getData = Role::query()->find($id);
               $dataReturnMess = 'Tìm dữ liệu thành công';
               $dataResponse = [
                   'status' => 200,
                   'message' => $dataReturnMess,
                   'data' => $getData,
               ];

           } else{
               $dataReturnMess = 'Không tìm thấy dữ liệu';
               $dataResponse = [
                   'status' => 422,
                   'message' => $dataReturnMess
               ];
           }

           return $dataResponse;
    }

    public function UpdateRoleData($data,$id)
    {
        if ((!empty($data) and $data !='') and (!empty($id) and $id !='') ){

            $dataUpdate = [
                'name' => $data['name'] ?? '',
                'title' => $data['title'] ?? '',
                'code' => $data['code'] ?? '',
            ];

            $getData = Role::query()->where('id',$id)->update($dataUpdate);
            $findData = Role::query()->find($id);
            $dataResponse = [
                'status' => 200,
                'data' => $findData,
            ];

        }elseif ($data == '' || $id == '')
        {
            $dataResponse = [
                'status' => 422,
            ];
        }

        return $dataResponse;
    }



}
