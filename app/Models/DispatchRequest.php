<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DispatchRequest extends Model
{
    protected $fillable = [
        'product_id',
        'requested_quantity',
        'status',
        'requested_by',
        'dispatched_by',
        'gdn_number',
        'deviation_reason',
        'requested_at',
        'dispatched_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_quantity' => 'decimal:3',
            'requested_at' => 'datetime',
            'dispatched_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DispatchLine::class);
    }
}
