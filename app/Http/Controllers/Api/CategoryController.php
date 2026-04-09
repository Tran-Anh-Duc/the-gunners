<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\BusinessActionRequest;
use App\Http\Requests\CategoryIndexRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Services\CategoryService;
use App\Traits\HasApiPagination;
use Illuminate\Http\JsonResponse;

class CategoryController extends ApiController
{
    use HasApiPagination;

    public function __construct(private readonly CategoryService $categoryService)
    {
    }

    public function index(CategoryIndexRequest $request): JsonResponse
    {
        [, $query] = $this->categoryService->paginate($request->validated());
		
		if ($request->boolean('is_option')) {
			return $this->successResponse(
				'Fetched successfully.',
				'list_success',
				self::HTTP_OK,
				$query->select(['id', 'name'])->get()
			);
		}
		
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
            $this->categoryService->show($id, $request->validated()),
        );
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        return $this->successResponse(
            'Created successfully.',
            'create_success',
            self::HTTP_OK,
            $this->categoryService->create($request->validated()),
        );
    }

    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->categoryService->update($id, $request->validated()),
        );
    }

    public function destroy(BusinessActionRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Deleted successfully.',
            'delete_success',
            self::HTTP_OK,
            $this->categoryService->delete($id, $request->validated()),
        );
    }
}
