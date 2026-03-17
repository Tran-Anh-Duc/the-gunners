<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessActionRequest;
use App\Http\Requests\BusinessIndexRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\ProductService;
use App\Traits\HasApiPagination;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    use HasApiPagination;

    public function __construct(private readonly ProductService $productService)
    {
    }

    public function index(BusinessIndexRequest $request): JsonResponse
    {
        [, $query] = $this->productService->paginate(array_merge(
            $request->validated(),
            $request->only($this->productService->searchableFilters()),
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
            $this->productService->show($id, $request->validated()),
        );
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        return $this->successResponse(
            'Created successfully.',
            'create_success',
            self::HTTP_OK,
            $this->productService->create($request->validated()),
        );
    }

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->productService->update($id, $request->validated()),
        );
    }

    public function destroy(BusinessActionRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Deleted successfully.',
            'delete_success',
            self::HTTP_OK,
            $this->productService->delete($id, $request->validated()),
        );
    }
}
