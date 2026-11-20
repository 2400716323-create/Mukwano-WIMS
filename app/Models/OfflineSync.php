<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfflineSync extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'operation',
        'payload_hash',
        'payload',
        'result_url',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'completed_at' => 'datetime',
        ];
    }
}
