<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventorySetting extends Model
{
    protected $fillable = [
        'id',
        'variance_threshold_percent',
        'accuracy_tolerance_percent',
        'updated_by',
        'company_name',
        'logo_path',
        'expiry_windows',
    ];

    protected function casts(): array
    {
        return [
            'variance_threshold_percent' => 'decimal:3',
            'accuracy_tolerance_percent' => 'decimal:3',
            'expiry_windows' => 'array',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate(
            ['id' => 1],
            [
                'variance_threshold_percent' => 5,
                'accuracy_tolerance_percent' => 0,
            ],
        );
    }

    /**
     * @return array{soon: int, warning: int, notice: int}
     */
    public function expiryWindows(): array
    {
        $windows = $this->expiry_windows ?? [];

        return [
            'soon' => (int) ($windows['soon'] ?? 30),
            'warning' => (int) ($windows['warning'] ?? 60),
            'notice' => (int) ($windows['notice'] ?? 90),
        ];
    }
}
