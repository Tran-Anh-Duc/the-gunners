<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserStatusRequest;
use App\Http\Requests\UpdateUserStatusRequest;
use App\Models\Action;
use App\Models\Role;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Permission;
use App\Repositories\RoleRepository;
use App\Repositories\UserStatusRepository;
use Illuminate\Http\JsonResponse;
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
    public function index(Request $request): JsonResponse
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
       return $this->handleRepoResult(
           result: $this->userStatusRepository->storeUserStatus($data),
           successMessage: __('messages.users_status.store_success'),
           code: 'store_success'
       );
    }

    public function show($id)
    {
        return $this->handleRepoResult(
            result: $this->userStatusRepository->showUserStatusById($id),
            successMessage: __('messages.users_status.show_success'),
            code: 'show_success',
        );
    }

    public function update(UpdateUserStatusRequest $request, $id)
    {
        $data = $request->all();
        return $this->handleRepoResult(
            result:  $this->userStatusRepository->updateUserStatus($data, $id),
            successMessage: __('messages.users_status.update_success'),
            code: 'update_success',
        );
    }

    public function destroy($id)
    {
        return $this->handleRepoResult(
            result: $this->userStatusRepository->destroyUserStatusById($id),
            successMessage: __('messages.users_status.delete_success'),
            code: 'delete_success',
        );
    }

    public function restore($id)
    {
        return $this->handleRepoResult(
            result:  $this->userStatusRepository->restoreUserStatusById($id),
            successMessage:  __('messages.users_status.restore_success'),
            code: 'restore_success'
        );
    }
}
