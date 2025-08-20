<?php

namespace App\Repositories;


use App\Models\Action;
use App\Models\Module;
use App\Models\Role;
use App\Repositories\BaseRepository;


class ModuleRepository extends BaseRepository
{
    //use CaculatePriceWareHouseTrait;

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




}
