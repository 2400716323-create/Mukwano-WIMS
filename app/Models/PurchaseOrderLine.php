<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderLine extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'ordered_quantity',
        'received_quantity',
    ];

    protected function casts(): array
    {
        return [
            'ordered_quantity' => 'decimal:3',
            'received_quantity' => 'decimal:3',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function outstandingQuantity(): string
    {
        $orderedUnits = $this->quantityUnits($this->ordered_quantity);
        $receivedUnits = $this->quantityUnits($this->received_quantity);

        $outstanding = max(0, $orderedUnits - $receivedUnits);

        return intdiv($outstanding, 1000).'.'.str_pad((string) ($outstanding % 1000), 3, '0', STR_PAD_LEFT);
    }

    private function quantityUnits(string|int|float $quantity): int
    {
        [$whole, $fraction] = array_pad(explode('.', number_format((float) $quantity, 3, '.', '')), 2, '000');

        return ((int) $whole * 1000) + (int) $fraction;
    }
}
