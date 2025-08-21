<?php

namespace App\Repositories;


use App\Models\Action;
use App\Repositories\BaseRepository;


class ActionRepository extends BaseRepository
{
    //use CaculatePriceWareHouseTrait;

    public function __construct(Action $action)
    {
        return $this->model = $action;
    }

    public function getModel()
    {
        return Action::class;
    }

    public function store($data)
    {

        if (!empty($data) and $data != ''){
            $dataCreate = [
                'key' => $data['key'],
                'name' => $data['name'],
                'description' => $data['description']
            ];

            $getData = Action::create($dataCreate);

            $dataResponse = [
                'status' => 200,
                'message' => $dataReturnMess,
                'data' => $getData,
            ];
        }else{

            $dataResponse = [
                'status' => 422,
                'message' => $dataReturnMess
            ];
        }


        return  $dataResponse;
    }

    public function show($id)
    {
        if (!empty($id) and $id != ''){
            $getData = Action::find($id);
            if (!empty($getData) and $getData != ''){

                $dataResponse = [
                    'status' => 200,
                    'message' => $dataReturnMess,
                    'data' => $getData,
                ];
            }else{

                $dataResponse = [
                    'status' => 204,
                    'message' => $dataReturnMess,
                    'data' => '',
                ];
            }

        }else{

            $dataResponse = [
                'status' => 422,
                'message' => $dataReturnMess
            ];
        }


        return  $dataResponse;
    }

    public function updateAction($data,$id)
    {
        if ((!empty($data) and $data != '') and (!empty($id) and $id != '')) {

            $allowedFields = ['key', 'name', 'description'];
            $dataUpdate = [];

            foreach ($allowedFields as $value){
                if (array_key_exists($value,$data)){
                    $dataUpdate[$value] = $data[$value];
                }
            }

            $module = Action::find($id);
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


        }else{
            $dataResponse = [
                'status' => 422,
            ];
        }
        return  $dataResponse;
    }


}
