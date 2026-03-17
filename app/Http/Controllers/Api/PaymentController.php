<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessActionRequest;
use App\Http\Requests\BusinessIndexRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\TransitionDocumentStatusRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Services\PaymentService;
use App\Traits\HasApiPagination;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    use HasApiPagination;

    public function __construct(private readonly PaymentService $paymentService)
    {
    }

    public function index(BusinessIndexRequest $request): JsonResponse
    {
        [, $query] = $this->paymentService->paginate(array_merge(
            $request->validated(),
            $request->only($this->paymentService->searchableFilters()),
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
            $this->paymentService->show($id, $request->validated()),
        );
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        return $this->successResponse(
            'Created successfully.',
            'create_success',
            self::HTTP_OK,
            $this->paymentService->create($request->validated()),
        );
    }

    public function update(UpdatePaymentRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->paymentService->update($id, $request->validated()),
        );
    }

    public function confirm(TransitionDocumentStatusRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Status updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->paymentService->confirm($id, $request->validated()),
        );
    }

    public function cancel(TransitionDocumentStatusRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Status updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->paymentService->cancel($id, $request->validated()),
        );
    }
}
