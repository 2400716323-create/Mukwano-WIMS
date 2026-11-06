<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = [
        'parent_id',
        'type',
        'name',
        'code',
        'status',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(BinInventory::class);
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function incomingStockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'to_location_id');
    }

    public function outgoingStockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'from_location_id');
    }

    public function isOperational(): bool
    {
        $location = $this;

        while ($location !== null) {
            if ($location->status !== 'active') {
                return false;
            }

            $location = $location->parent;
        }

        return true;
    }

    public function childType(): ?string
    {
        return match ($this->type) {
            'warehouse' => 'zone',
            'zone' => 'rack',
            'rack' => 'bin',
            default => null,
        };
    }
}
