<?php

namespace App\Http\Requests;

use App\Models\ManualStockAdjustment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewManualStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $adjustment = $this->route('manualStockAdjustment');

        return $adjustment instanceof ManualStockAdjustment
            && ($this->user()?->can('review', $adjustment) ?? false);
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'reason' => [
                'required_if:decision,rejected',
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
