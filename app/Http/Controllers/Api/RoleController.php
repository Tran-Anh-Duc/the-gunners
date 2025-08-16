<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Action;
use App\Repositories\RoleRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;
use App\Traits\HasApiPagination;
use function Carbon\this;

class RoleController extends Controller
{
    use ApiResponse;
    use HasApiPagination;
    protected $roleRepository;

    public function __construct(RoleRepository $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }


    public function index()
    {
        try {
            $query = $this->roleRepository->getList();
            $data = $this->paginate($query['data']);
            return $this->successResponse(
                __('messages.action_list'),
                'action_list',
                Controller::HTTP_OK,
                $data,
            );
        }catch (\Exception $e)
        {
            return $this->errorResponse(
                __('messages.action_failed'),
                'action_failed',
                Controller::ERRORS,
                $get_data,
            );
        }
    }

    public function store(Request $request)
    {
        //chỉ lấy dữ liệu từ body
        $data = $request->post();
        DB::beginTransaction();
        try {
            $resultData = $this->roleRepository->storeData($data);
            if (!empty($resultData) and $resultData['status'] == 200){
                $getData = $resultData['data'];
                DB::commit();
                return $this->successResponse(
                    __('messages.create_success'),
                    'create_success',
                    Controller::HTTP_OK,
                    $getData,
                );
            }elseif (!empty($resultData) and $resultData['status'] != 200){
                return $this->errorResponse(
                    __('messages.create_failed'),
                    'create_failed',
                    Controller::ERRORS,
                    '',

                );
            }
        }catch (\Exception $e)
        {
            return $this->errorResponse(
                __('messages.create_failed'),
                'create_failed',
                Controller::ERRORS,
                $get_data,
            );
        }
    }

}
