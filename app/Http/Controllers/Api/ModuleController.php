<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Action;
use App\Models\Module;
use App\Models\Role;
use App\Repositories\ModuleRepository;
use App\Repositories\RoleRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;
use App\Traits\HasApiPagination;
use function Carbon\this;

class ModuleController extends Controller
{
    use ApiResponse;
    use HasApiPagination;
    protected $moduleRepository;

    public function __construct(ModuleRepository $moduleRepository)
    {
        $this->moduleRepository = $moduleRepository;
    }


    public function index()
    {
        try {
            $query = $this->moduleRepository->getList();
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
                '',
            );
        }
    }

    public function store(Request $request)
    {
        $data = $request->all();
        DB::beginTransaction();

        try {
            $getData = $this->moduleRepository->storeData($data);
            if (!empty($getData) and $getData['status'] == 200){
                DB::commit();
                return $this->successResponse(
                    __('messages.create_success'),
                    'create_success',
                    Controller::HTTP_OK,
                    $getData['data'],
                );
            }elseif (!empty($getData) and $getData['status'] != 200){
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
                '',
            );
        }


    }

    public function show($id)
    {
        try {
            $getData = $this->moduleRepository->ShowData($id);
            if (!empty($getData) and $getData['status'] == 200) {
                return $this->successResponse(
                    __('messages.find_record_success'),
                    'find_record_success',
                    Controller::HTTP_OK,
                    $getData['data'],
                );
            } else {
                return $this->errorResponse(
                    __('messages.record_not_found'),
                    'record_not_found',
                    Controller::ERRORS,
                    '',
            );
            }

        }catch (\Exception $e)
        {
            return $this->errorResponse(
                __('messages.not_found_id'),
                'not_found_id',
                Controller::ERRORS,
                '',
            );
        }
    }


    public function update(Request $request, $id)
    {
        $data = $request->all();

        DB::beginTransaction();
        try {
            $getData = $this->moduleRepository->UpdateRoleData($data, $id);

            if (!empty($getData) and $getData['status'] == 200) {

                DB::commit();
                return $this->successResponse(
                    __('messages.update_success'),
                    'update_success',
                    Controller::HTTP_OK,
                    $getData['data'],
                );
            } else {
                return $this->errorResponse(
                    __('messages.update_failed'),
                    'update_failed',
                    Controller::ERRORS,
                    '',
            );
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse(
                __('messages.update_failed'),
                'update_failed',
                Controller::ERRORS,
                '',
            );
        }

    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            if (!empty($id) && $id != ''){
                $action = Module::query()->where('id',$id)->first();
                $action->delete();
                DB::commit();
                return $this->successResponse(
                    __('messages.delete_success'),
                    'delete_success',
                    Controller::HTTP_OK,
                    $action,
                );
            }else{
                return $this->errorResponse(
                    __('messages.delete_failed'),
                    'delete_failed',
                    Controller::HTTP_UNPROCESSABLE_ENTITY,
                    '',
                 );
            }


        }catch (\Exception $e){
            \Log::error($e);
            DB::rollBack();
            return $this->errorResponse(
                $e->getMessage(),
                'delete_failed',
                500,
                '',
            );
        }
    }

    public function restore($id)
    {
        DB::beginTransaction();
        try {
            $action = Module::withTrashed()->find($id);
            if ($action) {
                $action->restore();
                DB::commit();
                return $this->successResponse(
                    __('messages.successful_recovery'),
                    'successful_recovery',
                    Controller::HTTP_OK,
                    $action,
                );
            }else{
                return $this->errorResponse(
                    __('messages.restore_failed'),
                    'restore_failed',
                    Controller::HTTP_UNPROCESSABLE_ENTITY,
                    '',
                 );
            }
        }catch (\Exception $e){
            \Log::error($e);
            DB::rollBack();
            return $this->errorResponse(
                $e->getMessage(),
                'restore_failed',
                500,
                '',
            );
        }
    }


}
