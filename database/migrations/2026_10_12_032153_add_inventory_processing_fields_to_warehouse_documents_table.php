<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

	public function up(): void
	{
		Schema::table('warehouse_documents', function (Blueprint $table) {
			$table->string('inventory_status', 20)
				->nullable()
				->comment('Trạng thái xử lý tồn kho: pending, processing, completed, failed')
				->after('status');

			$table->timestamp('inventory_processing_started_at')
				->nullable()
				->comment('Thời điểm Worker bắt đầu xử lý hạch toán tồn kho')
				->after('inventory_status');

			$table->timestamp('inventory_processed_at')
				->nullable()
				->comment('Thời điểm hạch toán tồn kho hoàn tất thành công')
				->after('inventory_processing_started_at');

			$table->text('inventory_error')
				->nullable()
				->comment('Thông tin lỗi xử lý tồn kho gần nhất, phục vụ kiểm tra và khắc phục')
				->after('inventory_processed_at');
		});
	}

	public function down(): void
	{
		Schema::table('warehouse_documents', function (Blueprint $table) {

			$table->dropColumn([
				'inventory_status',
				'inventory_processing_started_at',
				'inventory_processed_at',
				'inventory_error',
			]);
		});
	}
};
