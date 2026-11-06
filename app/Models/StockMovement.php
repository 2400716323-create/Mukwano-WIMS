<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'batch_id',
        'from_location_id',
        'to_location_id',
        'goods_receipt_line_id',
        'stock_count_line_id',
        'performed_by',
        'approved_by',
        'movement_type',
        'reason',
        'quantity',
        'reference_type',
        'reference_id',
        'idempotency_key',
        'moved_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'moved_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function receiptLine(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptLine::class, 'goods_receipt_line_id');
    }

    public function countLine(): BelongsTo
    {
        return $this->belongsTo(StockCountLine::class, 'stock_count_line_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
