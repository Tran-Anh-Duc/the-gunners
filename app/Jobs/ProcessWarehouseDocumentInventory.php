<?php

namespace App\Jobs;

use App\Models\WarehouseDocument;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Repositories\WarehouseDocumentRepository;

class ProcessWarehouseDocumentInventory implements ShouldQueue
{
	use Queueable;

	public int $tries = 3;

	public int $timeout = 120;

	public function __construct(
		public readonly int $documentId
	) {}

	public function handle(
		WarehouseDocumentRepository $warehouseDocumentRepository
	): void {
		$claimed = $warehouseDocumentRepository->claimInventoryProcessing($this->documentId);


		if (!$claimed) {
			return;
		}

		// TODO: Gọi service hạch toán tồn kho.
	}
}
