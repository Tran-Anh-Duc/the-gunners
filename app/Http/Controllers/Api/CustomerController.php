<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessActionRequest;
use App\Http\Requests\BusinessIndexRequest;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Services\CustomerService;
use App\Traits\HasApiPagination;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
    use HasApiPagination;

    public function __construct(private readonly CustomerService $customerService)
    {
    }

    public function index(BusinessIndexRequest $request): JsonResponse
    {
        [, $query] = $this->customerService->paginate(array_merge(
            $request->validated(),
            $request->only($this->customerService->searchableFilters()),
        ));

        return $this->successResponse(
            'Fetched successfully.',
            'list_success',
            self::HTTP_OK,
            $this->paginate($query, defaultSort: ['column' => 'id', 'order' => 'desc'], defaultPerPage: 10),
        );
    }

    public function show(BusinessActionRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Fetched successfully.',
            'show_success',
            self::HTTP_OK,
            $this->customerService->show($id, $request->validated()),
        );
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        return $this->successResponse(
            'Created successfully.',
            'create_success',
            self::HTTP_OK,
            $this->customerService->create($request->validated()),
        );
    }

    public function update(UpdateCustomerRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->customerService->update($id, $request->validated()),
        );
    }

    public function destroy(BusinessActionRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Deleted successfully.',
            'delete_success',
            self::HTTP_OK,
            $this->customerService->delete($id, $request->validated()),
        );
    }
}
