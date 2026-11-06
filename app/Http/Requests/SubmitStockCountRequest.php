<?php

namespace App\Http\Requests;

use App\Models\OfflineSync;
use App\Models\StockCount;
use Illuminate\Foundation\Http\FormRequest;

class SubmitStockCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $stockCount = $this->route('stockCount');
        if ($this->routeIs('offline.sync.count')
            && $this->user() !== null
            && is_string($this->input('client_id'))
            && OfflineSync::query()
                ->whereKey($this->input('client_id'))
                ->where('user_id', $this->user()->id)
                ->where('operation', 'count_submission')
                ->exists()) {
            return true;
        }

        return $stockCount instanceof StockCount
            && ($this->user()?->can('submit', $stockCount) ?? false);
    }

    public function rules(): array
    {
        return [
            'client_id' => ['nullable', 'uuid'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.physical_quantity' => ['required', 'numeric', 'gte:0', 'decimal:0,3'],
            'lines.*.discrepancy_reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
