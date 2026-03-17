<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

class UserController extends Controller
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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(UserIndexRequest $request)
    {
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

    public function show(BusinessActionRequest $request, int $id): JsonResponse
    {
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

    public function store(StoreUserRequest $request): JsonResponse
    {
        return $this->successResponse(
            __('messages.create_success'),
            'create_success',
            Controller::HTTP_OK,
            $this->transformData($this->userService->create($request->validated()), $this->userTransform)['data'],
        );
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            __('messages.update_success'),
            'update_success',
            Controller::HTTP_OK,
            $this->transformData($this->userService->update($id, $request->validated()), $this->userTransform)['data'],
        );
    }

    public function destroy(BusinessActionRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            __('messages.delete_success'),
            'delete_success',
            Controller::HTTP_OK,
            $this->userService->delete($id, $request->validated()),
        );
    }
}
