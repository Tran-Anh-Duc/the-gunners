<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\BusinessActionRequest;
use App\Http\Requests\BusinessIndexRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\ProductService;
use App\Traits\HasApiPagination;
use Illuminate\Http\JsonResponse;

/**
 * Controller CRUD sản phẩm.
 *
 * Sản phẩm là master data trung tâm của bài toán bán hàng và tồn kho,
 * nhưng controller này chỉ làm nhiệm vụ nhận request, gọi service và trả response.
 */
class ProductController extends ApiController
{
    use HasApiPagination;

    public function __construct(private readonly ProductService $productService)
    {
    }

    /**
     * Trả danh sách sản phẩm trong business hiện tại.
     *
     * Thành phần đầu vào:
     * - page/per_page/sort
     * - sku, name, barcode, status
     *
     * Cách xử lý:
     * - ProductService::paginate()
     * - service sẽ áp business scope, filter và eager load `unit`
     */
    public function index(BusinessIndexRequest $request): JsonResponse
    {
        // Controller giữ mỏng: lấy filter đã validate rồi chuyển toàn bộ phần xử lý cho service.
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

    /**
     * Lấy chi tiết một sản phẩm.
     *
     * Logic:
     * - request đã bảo đảm có business context;
     * - service sẽ chỉ đọc bản ghi thuộc đúng business hiện tại.
     */
    public function show(BusinessActionRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Fetched successfully.',
            'show_success',
            self::HTTP_OK,
            $this->productService->show($id, $request->validated()),
        );
    }

    /**
     * Tạo sản phẩm mới.
     *
     * Logic xử lý:
     * - kiểm tra `unit_id` có thuộc business hiện tại hay không;
     * - tự bổ sung giá trị mặc định như `product_type`, `track_inventory`, `status`;
     * - gắn `business_id` ở tầng repository để tránh client can thiệp.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        // Product chỉ được tạo trong tenant hiện tại; controller không xử lý nghiệp vụ trực tiếp.
        return $this->successResponse(
            'Created successfully.',
            'create_success',
            self::HTTP_OK,
            $this->productService->create($request->validated()),
        );
    }

    /**
     * Cập nhật sản phẩm.
     *
     * Nếu request gửi `unit_id`, service sẽ kiểm tra lại tính hợp lệ theo business scope.
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        return $this->successResponse(
            'Updated successfully.',
            'update_success',
            self::HTTP_OK,
            $this->productService->update($id, $request->validated()),
        );
    }

    /**
     * Xóa sản phẩm theo business scope.
     *
     * Điều này giúp tránh xóa nhầm sản phẩm của tenant khác dù ID có tồn tại.
     */
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
