<?php

namespace App\Models;

use App\Support\BusinessSequenceGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseDocument extends Model
{
    use SoftDeletes;

    public const TYPE_IMPORT = 'import';

    public const TYPE_EXPORT = 'export';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'business_id',
        'document_code',
        'document_type',
        'warehouse_id',
        'document_date',
        'status',
        'reference_code',
        'subtotal_amount',
        'tax_amount',
        'total_amount',
        'note',
        'created_by',
        'updated_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'approved_at' => 'datetime',
            'subtotal_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }
	
	protected static function booted(): void
	{
		static::creating(function (self $document): void {
			if (!empty($document->document_code) || empty($document->business_id)) {
				return;
			}
			
			$prefix = match ($document->document_type) {
				'import' => 'WH-IMPORT',
				'export' => 'WH-EXPORT',
				default => null,
			};
			
			if ($prefix === null) {
				return;
			}
			
			$document->document_code = BusinessSequenceGenerator::nextFormatted(
				self::class,
				(int)$document->business_id,
				'document_code_' . $document->document_type,
				$prefix
			);
		});
	}
	
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(WarehouseDocumentDetail::class);
    }
}
