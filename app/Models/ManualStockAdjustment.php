<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualStockAdjustment extends Model
{
    protected $fillable = [
        'product_id',
        'batch_id',
        'location_id',
        'requested_by',
        'reviewed_by',
        'current_quantity',
        'adjustment_quantity',
        'direction',
        'reason',
        'status',
        'review_note',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'current_quantity' => 'decimal:3',
            'adjustment_quantity' => 'decimal:3',
            'reviewed_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
