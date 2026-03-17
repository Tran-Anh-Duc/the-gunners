<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessActionRequest;
use App\Http\Requests\BusinessIndexRequest;
use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Services\UnitService;
use App\Traits\HasApiPagination;
use Illuminate\Http\JsonResponse;

class UnitController extends Controller
{
    use HasApiPagination;

    public function __construct(private readonly UnitService $unitService)
    {
    }

    public function index(BusinessIndexRequest $request): JsonResponse
    {
        [, $query] = $this->unitService->paginate(array_merge(
            $request->validated(),
            $request->only($this->unitService->searchableFilters()),
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
            $this->unitService->show($id, $request->validated()),
        );
    }

    public function store(StoreUnitRequest $request): JsonResponse
    {
        return $this->successResponse(
            'Created successfully.',
            'create_success',
            self::HTTP_OK,
            $this->unitService->create($request->validated()),
        );
    }

    public function update(UpdateUnitRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->unitService->update($id, $request->validated()),
        );
    }

    public function destroy(BusinessActionRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Deleted successfully.',
            'delete_success',
            self::HTTP_OK,
            $this->unitService->delete($id, $request->validated()),
        );
    }
}
