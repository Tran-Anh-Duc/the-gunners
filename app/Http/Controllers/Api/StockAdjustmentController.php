<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessActionRequest;
use App\Http\Requests\BusinessIndexRequest;
use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Http\Requests\TransitionDocumentStatusRequest;
use App\Http\Requests\UpdateStockAdjustmentRequest;
use App\Services\StockAdjustmentService;
use App\Traits\HasApiPagination;
use Illuminate\Http\JsonResponse;

class StockAdjustmentController extends Controller
{
    use HasApiPagination;

    public function __construct(private readonly StockAdjustmentService $stockAdjustmentService)
    {
    }

    public function index(BusinessIndexRequest $request): JsonResponse
    {
        [, $query] = $this->stockAdjustmentService->paginate(array_merge(
            $request->validated(),
            $request->only($this->stockAdjustmentService->searchableFilters()),
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
            $this->stockAdjustmentService->show($id, $request->validated()),
        );
    }

    public function store(StoreStockAdjustmentRequest $request): JsonResponse
    {
        return $this->successResponse(
            'Created successfully.',
            'create_success',
            self::HTTP_OK,
            $this->stockAdjustmentService->create($request->validated()),
        );
    }

    public function update(UpdateStockAdjustmentRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->stockAdjustmentService->update($id, $request->validated()),
        );
    }

    public function confirm(TransitionDocumentStatusRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Status updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->stockAdjustmentService->confirm($id, $request->validated()),
        );
    }

    public function cancel(TransitionDocumentStatusRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Status updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->stockAdjustmentService->cancel($id, $request->validated()),
        );
    }
}
