<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\BusinessActionRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UserIndexRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Services\UserService;
use App\Traits\ApiResponse;
use App\Traits\HasApiPagination;
use App\Transformers\UserTransform;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use App\Http\Controllers\Controller;

/**
 * Controller quản lý user trong business hiện tại.
 *
 * Controller này chỉ làm delivery layer:
 * - request lo validate;
 * - `UserService` lo nghiệp vụ;
 * - `UserTransform` lo định dạng dữ liệu trả ra.
 */
class UserController extends ApiController
{
    use ApiResponse;
    use HasApiPagination;
    public function __construct(
        protected UserTransform $userTransform,
        protected UserService $userService,
    )
    {
    }

    /**
     * Danh sách user trong business hiện tại.
     *
     * Flow:
     * - `UserIndexRequest` validate filter;
     * - `UserService` trả query đã scope;
     * - controller paginate và transform bằng `UserTransform`.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(UserIndexRequest $request)
    {
        // Controller chỉ giữ phần presentation: lấy query từ service rồi paginate/transform.
        $pagination = $this->paginate(
            query: $this->userService->listQuery($request->validated()),
            transformer: $this->userTransform,
            defaultPerPage: 10,
        );

        return $this->successResponse(
            message: __('messages.user.user_list_success'),
            code: 'user_list_success',
            httpStatus: Controller::HTTP_OK,
            data: $pagination,
        );
    }

    /**
     * Chi tiết 1 user trong business hiện tại.
     */
    public function show(BusinessActionRequest $request, int $id): JsonResponse
    {
        // Service trả model đã scope theo business; controller chỉ transform để frontend dễ dùng.
        $dataTranById = $this->transformData(
            $this->userService->show($id, $request->validated()),
            $this->userTransform,
        )['data'];

        return $this->successResponse(
            message: __('messages.user.user_info_success'),
            code: 'user_info_success',
            httpStatus: Controller::HTTP_OK,
            data: $dataTranById,
        );
    }

    /**
     * Tạo user mới.
     *
     * `UserService` sẽ tạo:
     * - record trong bảng `users`;
     * - membership trong `business_users`.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        // Controller không trộn logic tài khoản và membership; mọi thứ do service điều phối.
        return $this->successResponse(
            __('messages.create_success'),
            'create_success',
            Controller::HTTP_OK,
            $this->transformData($this->userService->create($request->validated()), $this->userTransform)['data'],
        );
    }

    /**
     * Cập nhật user và/hoặc membership trong business hiện tại.
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        // Service sẽ tách rõ phần update tài khoản hệ thống và phần membership của business.
        return $this->successResponse(
            __('messages.update_success'),
            'update_success',
            Controller::HTTP_OK,
            $this->transformData($this->userService->update($id, $request->validated()), $this->userTransform)['data'],
        );
    }

    /**
     * Xóa user khỏi business hiện tại.
     *
     * Nếu user không còn membership nào khác, service mới xóa record user hệ thống.
     */
    public function destroy(BusinessActionRequest $request, int $id): JsonResponse
    {
        // Đây là xóa theo business scope, không phải lúc nào cũng là xóa user toàn hệ thống.
        return $this->successResponse(
            __('messages.delete_success'),
            'delete_success',
            Controller::HTTP_OK,
            $this->userService->delete($id, $request->validated()),
        );
    }
}
