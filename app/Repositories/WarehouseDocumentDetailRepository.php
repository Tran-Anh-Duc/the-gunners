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
	 * @param array $rows
	 * @return bool `true` nếu insert thành công hoặc danh sách rỗng.
	 */
	public function insertRows(array $rows): bool
	{
		if (empty($rows)) {
			return true;
		}
		
		return $this->model->newQuery()->insert($rows);
	}
	
	/**
	 * Cập nhật danh sách chi tiết theo kiểu replace-all:
	 * xóa chi tiết cũ của chứng từ và tạo lại toàn bộ từ payload mới.
	 *
	 * @param int $documentId ID chứng từ kho cha.
	 * @param int $businessId ID business (giữ lại để tương thích chữ ký hiện tại).
	 * @param array<int, array<string, mixed>> $details Danh sách chi tiết mới.
	 *
	 * @return array `true` nếu thao tác thành công hoặc danh sách rỗng.
	 */
	public function updateAndCreateManyForDocument(int $documentId, int $businessId, array $details): array
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
	
	
	
}
