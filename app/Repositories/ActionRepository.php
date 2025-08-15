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
            $dataReturnMess = 'Tạo dữ liệu thành công';
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


        return  $dataResponse;
    }

    public function show($id)
    {
        if (!empty($id) and $id != ''){
            $getData = Action::find($id);
            if (!empty($getData) and $getData != ''){
                $dataReturnMess = 'Tìm dữ liệu thành công';
                $dataResponse = [
                    'status' => 200,
                    'message' => $dataReturnMess,
                    'data' => $getData,
                ];
            }else{
                $dataReturnMess = 'Không tìm thấy dữ liệu';
                $dataResponse = [
                    'status' => 204,
                    'message' => $dataReturnMess,
                    'data' => '',
                ];
            }

        }else{
            $dataReturnMess = 'Không tìm thấy dữ liệu';
            $dataResponse = [
                'status' => 422,
                'message' => $dataReturnMess
            ];
        }


        return  $dataResponse;
    }

    public function updateAction($data,$id)
    {
        if ((!empty($data) and $data !='') and (!empty($id) and $id != '')){

            $dataUpdate = [
                'key' => $data['key'],
                'name' => $data['name'],
                'description' => $data['description'],
            ];
            $getData = Action::query()->where('id',$id)->update($dataUpdate);

            $dataReturnMess = 'Update dữ liệu thành công';
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
        return  $dataResponse;
    }


}
