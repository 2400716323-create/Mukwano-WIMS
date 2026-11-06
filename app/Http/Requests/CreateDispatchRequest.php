<?php

namespace App\Http\Requests;

use App\Models\DispatchRequest as DispatchRequestModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateDispatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', DispatchRequestModel::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('status', 'active'),
            ],
            'quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3'],
        ];
    }
}
