<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InventoryIndexRequest;
use App\Services\InventoryService;
use App\Traits\HasApiPagination;
use Illuminate\Http\JsonResponse;

class InventoryController extends Controller
{
    use HasApiPagination;

    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function index(InventoryIndexRequest $request): JsonResponse
    {
        [, $query] = $this->inventoryService->paginate($request->validated());

        return $this->successResponse(
            'Fetched successfully.',
            'list_success',
            self::HTTP_OK,
            $this->paginate($query, defaultSort: ['column' => 'id', 'order' => 'desc'], defaultPerPage: 20),
        );
    }
}
