<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PutAwayTask extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'task_number', 'stock_balance_id', 'batch_id', 'source_location_id',
        'destination_location_id', 'quantity', 'status', 'assigned_to',
        'created_by', 'completed_by', 'notes', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function stockBalance(): BelongsTo { return $this->belongsTo(StockBalance::class); }
    public function batch(): BelongsTo { return $this->belongsTo(Batch::class); }
    public function sourceLocation(): BelongsTo { return $this->belongsTo(Location::class, 'source_location_id'); }
    public function destinationLocation(): BelongsTo { return $this->belongsTo(Location::class, 'destination_location_id'); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function completer(): BelongsTo { return $this->belongsTo(User::class, 'completed_by'); }
}
