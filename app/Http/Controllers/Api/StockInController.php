<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessActionRequest;
use App\Http\Requests\BusinessIndexRequest;
use App\Http\Requests\StoreStockInRequest;
use App\Http\Requests\TransitionDocumentStatusRequest;
use App\Http\Requests\UpdateStockInRequest;
use App\Services\StockInService;
use App\Traits\HasApiPagination;
use Illuminate\Http\JsonResponse;

class StockInController extends Controller
{
    use HasApiPagination;

    public function __construct(private readonly StockInService $stockInService)
    {
    }

    public function index(BusinessIndexRequest $request): JsonResponse
    {
        [, $query] = $this->stockInService->paginate(array_merge(
            $request->validated(),
            $request->only($this->stockInService->searchableFilters()),
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
            $this->stockInService->show($id, $request->validated()),
        );
    }

    public function store(StoreStockInRequest $request): JsonResponse
    {
        return $this->successResponse(
            'Created successfully.',
            'create_success',
            self::HTTP_OK,
            $this->stockInService->create($request->validated()),
        );
    }

    public function update(UpdateStockInRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->stockInService->update($id, $request->validated()),
        );
    }

    public function confirm(TransitionDocumentStatusRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Status updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->stockInService->confirm($id, $request->validated()),
        );
    }

    public function cancel(TransitionDocumentStatusRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Status updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->stockInService->cancel($id, $request->validated()),
        );
    }
}
