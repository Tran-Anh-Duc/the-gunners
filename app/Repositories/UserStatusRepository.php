<?php

namespace App\Repositories;


use App\Models\Action;
use App\Models\Role;
use App\Models\UserStatus;
use App\Repositories\BaseRepository;
use function Termwind\ValueObjects\pr;


class UserStatusRepository extends BaseRepository
{
    //use CaculatePriceWareHouseTrait;

    public function __construct(UserStatus $userStatus)
    {
        return $this->model = $userStatus;
    }

    public function getModel()
    {
        return UserStatus::class;
    }

    public function getList()
    {
        $getAll = UserStatus::query();

        $dataResponse = [
            'status' => 200,
            'data' => $getAll,
        ];

        return $dataResponse;
    }

    public function storeData($data)
    {
        if (!empty($data) and $data !=''){
            $dataCreate = [
                'name' => $data['name'],
                'description' => $data['description'],
            ];

            $getData = UserStatus::query()->create($dataCreate);
            $dataResponse = [
                'status' => 200,
                'data' => $getData,
            ];
        }else{

            $dataResponse = [
                'status' => 422,
            ];
        }

        return $dataResponse;
    }


    public function ShowData($id)
    {
           if (!empty($id) and $id != ''){

               $getData = UserStatus::query()->find($id);
               $dataResponse = [
                   'status' => 200,
                   'data' => $getData,
               ];

           } else{
               $dataResponse = [
                   'status' => 422,
               ];
           }

           return $dataResponse;
    }

    public function UpdateRoleData($data,$id)
    {
        if ((!empty($data) and $data !='') and (!empty($id) and $id !='') ){

            $allowedFields = ['name','description'];
            $dataUpdate = [];

            foreach ($allowedFields as $value){
                if (array_key_exists($value,$data)){
                    $dataUpdate[$value] = $data[$value];
                }
            }

            $findData = UserStatus::query()->find($id);
            $findData->update($dataUpdate);

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
