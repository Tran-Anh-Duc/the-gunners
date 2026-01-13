<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserStatusRequest;
use App\Models\Action;
use App\Models\Role;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Permission;
use App\Repositories\RoleRepository;
use App\Repositories\UserStatusRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;
use App\Traits\HasApiPagination;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use function Carbon\this;
use Throwable;

class UserStatusController extends Controller
{
    use ApiResponse;
    use HasApiPagination;
    protected UserStatusRepository $userStatusRepository;

    public function __construct(UserStatusRepository $userStatusRepository)
    {
        $this->userStatusRepository = $userStatusRepository;
    }


    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(Request $request)
    {
        $search = $request->query('name');
        $query = $this->userStatusRepository->getAllListUserStatus($search);
        $data = $this->paginate($query);
        return $this->successResponse(
            __('messages.users_status.list_success'),
            'list_success',
            Controller::HTTP_OK,
            $data,
        );
    }

    public function store(StoreUserStatusRequest $request)
    {
        $data = $request->all();
        $resultData = $this->userStatusRepository->storeUserStatus($data);
        return $this->successResponse(
            message: __('messages.user_status.create_success'),
            code: 'create_success',
            httpStatus: Controller::HTTP_OK,
            data: $resultData,
        );

    }

    public function show($id)
    {
        $getData = $this->userStatusRepository->ShowUserStatusByID($id);
        return $this->successResponse(
            message: __('messages.users_status.user_status_show_success'),
            code: 'user_status_show_success',
            httpStatus: Controller::HTTP_OK,
            data: $getData,
        );
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();

        DB::beginTransaction();
        try {
            $getData = $this->userStatusRepository->UpdateRoleData($data, $id);
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
                $e->getMessage(),
            );
        }

    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            if (!empty($id) && $id != ''){
                $action = UserStatus::query()->where('id',$id)->first();
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
                $e->getMessage(),
            );
        }
    }

    public function restore($id)
    {
        DB::beginTransaction();
        try {
            $action = UserStatus::withTrashed()->find($id);
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
                $e->getMessage(),
            );
        }
    }




}
