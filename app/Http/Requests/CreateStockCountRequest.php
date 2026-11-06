<?php

namespace App\Http\Requests;

use App\Models\Batch;
use App\Models\Location;
use App\Models\StockCount;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateStockCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', StockCount::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'location_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')->where('type', 'bin')->where('status', 'active'),
            ],
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')->where('status', 'active')],
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'blind_count' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('product_id') && ! $this->filled('batch_id')) {
                $validator->errors()->add('product_id', 'Select a product or batch for this count.');
            }

            if ($this->filled('batch_id') && $this->filled('product_id')) {
                $matchesProduct = Batch::query()
                    ->whereKey($this->input('batch_id'))
                    ->where('product_id', $this->input('product_id'))
                    ->exists();
                if (! $matchesProduct) {
                    $validator->errors()->add('batch_id', 'Choose a batch belonging to the selected product.');
                }
            }

            $location = $this->filled('location_id')
                ? Location::query()->find($this->input('location_id'))
                : null;
            if ($location !== null && ! $location->isOperational()) {
                $validator->errors()->add('location_id', 'Choose a bin with active ancestors.');
            }
        });
    }
}
