<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Repositories\UserRepository;
use App\Traits\ApiResponse;
use App\Traits\HasApiPagination;
use App\Transformers\UserTransform;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class UserController extends Controller
{
    use ApiResponse;
    use HasApiPagination;
    protected UserTransform $userTransform;
    protected UserRepository $userRepository;
    /**
     * Lấy danh sách tất cả user kèm role và permission.
     */

    public function __construct(UserTransform $userTransform , UserRepository $userRepository)
    {
        $this->userTransform = $userTransform;
        $this->userRepository = $userRepository;
    }

    /* lấy danh sách các users*/
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(Request $request)
    {
        $search = $request->query();
        $result = $this->userRepository->getListAllUser($search);
        $pagination = $this->paginate(query: $result,transformer: $this->userTransform,defaultPerPage: 10);

        return $this->successResponse(
            message: __('messages.user.user_list_success'),
            code: 'user_list_success',
            httpStatus: Controller::HTTP_OK,
            data: $pagination,
        );

    }

    /*show chi tiết từng user*/
    public function show($id): JsonResponse
    {
        $result = $this->userRepository->showUserById($id);
        $dataTranById = $this->transformData($result, $this->userTransform)['data'];
        return $this->successResponse(
            message: __('messages.user.user_info_success'),
            code: 'user_info_success',
            httpStatus: Controller::HTTP_OK,
            data: $dataTranById,
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $resultData = $this->userRepository->createUser($request->validated());

        if (($resultData['status'] ?? 422) !== 200) {
            return $this->errorResponse(
                __('messages.create_failed'),
                'create_failed',
                ($resultData['status'] ?? 422) === 404 ? Controller::HTTP_NOT_FOUND : Controller::HTTP_UNPROCESSABLE_ENTITY,
                '',
            );
        }

        return $this->successResponse(
            __('messages.create_success'),
            'create_success',
            Controller::HTTP_OK,
            $resultData['data'],
        );
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $resultData = $this->userRepository->updateUser($request->validated(), $id);

        if (($resultData['status'] ?? 422) !== 200) {
            return $this->errorResponse(
                ($resultData['status'] ?? 422) === 404 ? __('messages.record_not_found') : __('messages.update_failed'),
                ($resultData['status'] ?? 422) === 404 ? 'record_not_found' : 'update_failed',
                ($resultData['status'] ?? 422) === 404 ? Controller::HTTP_NOT_FOUND : Controller::HTTP_UNPROCESSABLE_ENTITY,
                '',
            );
        }

        return $this->successResponse(
            __('messages.update_success'),
            'update_success',
            Controller::HTTP_OK,
            $resultData['data'],
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $resultData = $this->userRepository->destroyUser($id);

        if (($resultData['status'] ?? 422) !== 200) {
            return $this->errorResponse(
                ($resultData['status'] ?? 422) === 404 ? __('messages.record_not_found') : __('messages.delete_failed'),
                ($resultData['status'] ?? 422) === 404 ? 'record_not_found' : 'delete_failed',
                ($resultData['status'] ?? 422) === 404 ? Controller::HTTP_NOT_FOUND : Controller::HTTP_UNPROCESSABLE_ENTITY,
                '',
            );
        }

        return $this->successResponse(
            __('messages.delete_success'),
            'delete_success',
            Controller::HTTP_OK,
            $resultData['data'],
        );
    }

    /**
     * Thêm mới hoặc cập nhật thông tin phòng ban cho nhân viên.
     *
     * @param Request $request Dữ liệu gửi lên từ frontend (Vue / Postman)
     * @param int $id ID của nhân viên (user_id)
     * @return JsonResponse
     *
     * Dữ liệu mẫu (JSON):
     * {
     *   "department_id": 3,
     *   "is_main": 0,
     *   "position": "1",
     *   "assigned_at": "2025-10-21 09:00:00",
     *   "action_type": 1 // 1 = thêm mới, 2 = cập nhật
     * }
     * @throws Throwable
     */
    public function create_user_department(Request $request, int $id)
    {
        $data = $request->all();
        if (!$data) {
            return $this->errorResponse(
                __('messages.action_failed'),
                'action_failed',
                Controller::ERRORS,
                '',
            );
        }else{

            DB::beginTransaction();
            try {
                $resultData = $this->userRepository->create_user_department($data,$id);
                if (!empty($resultData) and $resultData != ''){
                    if ($resultData['status'] == 200){
                        $getData = $resultData['data'];
                        DB::commit();
                        return $this->successResponse(
                            __('messages.create_success'),
                            'create_success',
                            Controller::HTTP_OK,
                            $getData,
                        );
                    }else{
                        return $this->errorResponse(
                            __('messages.create_failed'),
                            'create_failed',
                            Controller::ERRORS,
                            '',
                        );
                    }
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
    }


    /**
     * Thêm mới hoặc cập nhật thông tin phòng ban cho nhân viên.
     *
     * @param Request $request Dữ liệu gửi lên từ frontend (Vue / Postman)
     * @param int $id ID của bảng user_department (id)
     * @return JsonResponse
     *
     * Dữ liệu mẫu (JSON):
     * {
     *   "assigned_at": "2025-10-21 09:00:00",
     *   "action_type": 2 // 2 = cập nhật
     * }
     * @throws Throwable
     */
    public function update_user_department(Request $request, int $id)
    {
        $data = $request->all();
        if (!$data) {
            return $this->errorResponse(
                __('messages.action_failed'),
                'action_failed',
                Controller::ERRORS,
                '',
            );
        }else{

            DB::beginTransaction();
            try {
                $resultData = $this->userRepository->update_user_department($data,$id);
                if (!empty($resultData) and $resultData != ''){
                    if ($resultData['status'] == 200){
                        $getData = $resultData['data'];
                        DB::commit();
                        return $this->successResponse(
                            __('messages.update_success'),
                            'update_success',
                            Controller::HTTP_OK,
                            $getData,
                        );
                    }else{
                        return $this->errorResponse(
                            __('messages.update_failed'),
                            'update_failed',
                            Controller::ERRORS,
                            '',
                        );
                    }
                }
            }catch (\Exception $e)
            {
                return $this->errorResponse(
                    __('messages.update_failed'),
                    'update_failed',
                    Controller::ERRORS,
                    '',
                    );
            }
        }
    }




}
