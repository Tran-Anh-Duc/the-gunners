<?php

namespace App\Repositories;

use App\Models\WarehouseDocumentDetail;
use Carbon\Carbon;

/**
 * Repository thao tác dữ liệu chi tiết chứng từ kho.
 *
 * @author anhduc96
 */
class WarehouseDocumentDetailRepository extends BaseRepository
{
    /**
     * Trả về model mà repository đang quản lý.
     *
     * @return class-string<WarehouseDocumentDetail>
     */
    public function getModel()
    {
        return WarehouseDocumentDetail::class;
    }
	
	/**
	 * Tạo danh sách chi tiết cho một chứng từ kho.
	 *
	 * @param int $documentId ID chứng từ kho cha.
	 * @param int $businessId ID business (giữ lại để tương thích chữ ký hiện tại).
	 * @param array<int, array<string, mixed>> $details Danh sách chi tiết đầu vào.
	 *
	 * @return bool `true` nếu insert thành công hoặc danh sách rỗng.
	 */
	public function createManyForDocument(int $documentId, int $businessId, array $details): bool
	{
		unset($businessId);
		return $this->insertManyForDocument($documentId, $details);
	}
	
	/**
	 * Cập nhật danh sách chi tiết theo kiểu replace-all:
	 * xóa chi tiết cũ của chứng từ và tạo lại toàn bộ từ payload mới.
	 *
	 * @param int $documentId ID chứng từ kho cha.
	 * @param int $businessId ID business (giữ lại để tương thích chữ ký hiện tại).
	 * @param array<int, array<string, mixed>> $details Danh sách chi tiết mới.
	 *
	 * @return bool `true` nếu thao tác thành công hoặc danh sách rỗng.
	 */
	public function updateAndCreateManyForDocument(int $documentId, int $businessId, array $details): bool
	{
		
		unset($businessId);

		if (empty($details)) {
			return true;
		}
		
		$this->model->newQuery()
			->where('warehouse_document_id', $documentId)
			->delete();
		
		return $this->insertManyForDocument($documentId, $details);
	}

	/**
	 * Hàm dùng chung để insert nhiều dòng chi tiết cho chứng từ.
	 *
	 * @param int $documentId ID chứng từ kho cha.
	 * @param array<int, array<string, mixed>> $details Danh sách chi tiết.
	 *
	 * @return bool `true` nếu insert thành công hoặc danh sách rỗng.
	 */
	private function insertManyForDocument(int $documentId, array $details): bool
	{
		if (empty($details)) {
			return true;
		}

		$rows = $this->buildDetailRows($documentId, $details, Carbon::now());

		return $this->model->newQuery()->insert($rows);
	}
	
	/**
	 * Chuẩn hóa dữ liệu chi tiết đầu vào thành mảng rows để insert DB.
	 *
	 * @param int $documentId ID chứng từ kho cha.
	 * @param array<int, array<string, mixed>> $details Danh sách chi tiết.
	 * @param Carbon $now Mốc thời gian dùng cho `created_at` và `updated_at`.
	 *
	 * @return array<int, array<string, mixed>> Mảng dữ liệu đã chuẩn hóa để insert.
	 */
	private function buildDetailRows(int $documentId, array $details, Carbon $now): array
	{
		return array_map(function (array $detail) use ($documentId, $now): array {
			return [
				'warehouse_document_id' => $documentId,
				'product_id' => $detail['product_id'] ?? null,
				'product_name' => $detail['product_name'] ?? null,
				'unit_id' => $detail['unit_id'] ?? null,
				'unit_name' => $detail['unit_name'] ?? null,
				'quantity' => $detail['quantity'] ?? 0,
				'unit_price' => $detail['unit_price'] ?? 0,
				'subtotal' => $detail['subtotal'] ?? 0,
				'tax_rate' => $detail['tax_rate'] ?? 0,
				'tax_price' => $detail['tax_price'] ?? 0,
				'total_price' => $detail['total_price'] ?? 0,
				'note' => $detail['note'] ?? null,
				'created_at' => $now,
				'updated_at' => $now,
			];
		}, $details);
	}
	
}
