<?php

namespace App\Http\Requests;

use App\Models\StockCount;
use Illuminate\Foundation\Http\FormRequest;

class ReviewStockCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $stockCount = $this->route('stockCount');

        return $stockCount instanceof StockCount
            && ($this->user()?->can('review', $stockCount) ?? false);
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:approved,rejected'],
            'reason' => ['required_if:decision,rejected', 'nullable', 'string', 'max:2000'],
        ];
    }
}
