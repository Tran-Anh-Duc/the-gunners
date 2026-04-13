<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseDocumentDetail extends Model
{
    protected $fillable = [
        'warehouse_document_id',
        'product_id',
        'product_name',
        'unit_id',
        'unit_name',
        'quantity',
        'unit_price',
        'subtotal',
        'tax_rate',
        'tax_price',
        'total_price',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(WarehouseDocument::class, 'warehouse_document_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
