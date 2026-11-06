<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventorySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSupervisor() ?? false;
    }

    public function rules(): array
    {
        return [
            'variance_threshold_percent' => ['required', 'numeric', 'gte:0', 'lte:1000', 'decimal:0,3'],
            'accuracy_tolerance_percent' => ['required', 'numeric', 'gte:0', 'lte:100', 'decimal:0,3'],
        ];
    }
}
