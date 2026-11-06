<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    protected $fillable = [
        'product_id',
        'batch_number',
        'manufactured_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'manufactured_at' => 'date',
            'expires_at' => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function getExpiryStatusAttribute(): string
    {
        if ($this->expires_at === null) {
            return 'Good';
        }

        $windows = InventorySetting::query()->first()?->expiryWindows() ?? [
            'soon' => 30,
            'warning' => 60,
            'notice' => 90,
        ];
        $daysUntilExpiry = today()->diffInDays($this->expires_at->copy()->startOfDay(), false);
        if ($daysUntilExpiry < 0) {
            return 'Expired';
        }

        return match (true) {
            $daysUntilExpiry <= $windows['soon'] => "{$windows['soon']} days",
            $daysUntilExpiry <= $windows['warning'] => "{$windows['warning']} days",
            $daysUntilExpiry <= $windows['notice'] => "{$windows['notice']} days",
            default => 'Good',
        };
    }
}
