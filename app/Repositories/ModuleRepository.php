<?php

namespace App\Repositories;


use App\Models\Action;
use App\Models\Module;
use App\Models\Role;
use App\Repositories\BaseRepository;


class ModuleRepository extends BaseRepository
{

    public function __construct(Module $role)
    {
        return $this->model = $role;
    }

    public function getModel()
    {
        return Module::class;
    }

    public function getList()
    {
        $getAll = Module::query();

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

            $getData = Module::query()->create($dataCreate);
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

            $getData = Module::query()->find($id);
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

    public function UpdateRoleData($data, $id)
    {
        if ((!empty($data) and $data != '') and (!empty($id) and $id != '')) {

            $allowedFields = ['name', 'title', 'code'];
            $dataUpdate = [];

            foreach ($allowedFields as $value){
                if (array_key_exists($value,$data)){
                    $dataUpdate[$value] = $data[$value];
                }
            }

            $module = Module::find($id);
            if ($module) {
                $module->update($dataUpdate);

                $dataResponse = [
                    'status' => 200,
                    'data'   => $module,
                ];
            } else {
                $dataResponse = [
                    'status' => 404,
                    'message' => 'Module not found',
                ];
            }

        } else {
            $dataResponse = [
                'status' => 422,
            ];
        }

        return $dataResponse;
    }





}
