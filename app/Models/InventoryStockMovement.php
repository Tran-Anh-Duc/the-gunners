<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStockMovement extends Model
{
	public const SOURCE_INVENTORY_OPENING = 'inventory_opening';

	public const SOURCE_WAREHOUSE_DOCUMENT = 'warehouse_document';

	public const SOURCE_STOCK_ADJUSTMENT = 'stock_adjustment';

	public const SOURCE_STOCK_TRANSFER = 'stock_transfer';

	public const TYPE_OPENING = 'opening';

	public const TYPE_IMPORT = 'import';

	public const TYPE_EXPORT = 'export';

	public const TYPE_ADJUSTMENT_IN = 'adjustment_in';

	public const TYPE_ADJUSTMENT_OUT = 'adjustment_out';

	public const TYPE_TRANSFER_IN = 'transfer_in';

	public const TYPE_TRANSFER_OUT = 'transfer_out';

	protected $fillable = [
		'business_id',
		'warehouse_id',
		'product_id',
		'unit_id',
		'source_type',
		'source_id',
		'source_line_id',
		'movement_type',
		'movement_date',
		'posted_at',
		'quantity_delta',
		'unit_cost',
		'value_delta',
		'created_by',
	];

	protected function casts(): array
	{
		return [
			'movement_date' => 'date',
			'posted_at' => 'datetime',
			'quantity_delta' => 'decimal:4',
			'unit_cost' => 'decimal:4',
			'value_delta' => 'decimal:2',
		];
	}

	public function business(): BelongsTo
	{
		return $this->belongsTo(Business::class);
	}

	public function warehouse(): BelongsTo
	{
		return $this->belongsTo(Warehouse::class);
	}

	public function product(): BelongsTo
	{
		return $this->belongsTo(Product::class);
	}

	public function unit(): BelongsTo
	{
		return $this->belongsTo(Unit::class);
	}

	public function creator(): BelongsTo
	{
		return $this->belongsTo(User::class, 'created_by');
	}
}
