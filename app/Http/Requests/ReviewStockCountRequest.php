<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewStockCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled by StockCountController
        // using the StockCount policy.
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:approved,rejected'],
            'reason' => [
                'required_if:decision,rejected',
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
