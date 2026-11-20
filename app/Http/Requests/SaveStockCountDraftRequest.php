<?php

namespace App\Http\Requests;

use App\Models\StockCount;
use Illuminate\Foundation\Http\FormRequest;

class SaveStockCountDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        $stockCount = $this->route('stockCount');

        return $stockCount instanceof StockCount
            && ($this->user()?->can('submit', $stockCount) ?? false);
    }

    public function rules(): array
    {
        return [
            'lines' => ['sometimes', 'array'],
            'lines.*.physical_quantity' => ['nullable', 'numeric', 'gte:0', 'decimal:0,3'],
            'lines.*.discrepancy_reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
