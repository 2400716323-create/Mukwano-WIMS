<?php

namespace App\Http\Requests;

use App\Models\Batch;
use App\Models\Location;
use App\Models\ManualStockAdjustment;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManualStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ManualStockAdjustment::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('status', 'active'),
            ],
            'batch_id' => ['required', 'integer', 'exists:batches,id'],
            'location_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')
                    ->where('type', 'bin')
                    ->where('status', 'active'),
            ],
            'direction' => ['required', Rule::in(['increase', 'decrease'])],
            'adjustment_quantity' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,3',
                'max:999999999.999',
            ],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('batch_id') && $this->filled('product_id')) {
                $matchesProduct = Batch::query()
                    ->whereKey($this->input('batch_id'))
                    ->where('product_id', $this->input('product_id'))
                    ->exists();

                if (! $matchesProduct) {
                    $validator->errors()->add(
                        'batch_id',
                        'Choose a batch belonging to the selected product.'
                    );
                }
            }

            if ($this->filled('location_id')) {
                $location = Location::query()->find($this->input('location_id'));

                if ($location !== null && ! $location->isOperational()) {
                    $validator->errors()->add(
                        'location_id',
                        'Choose a bin with active ancestors.'
                    );
                }
            }
        });
    }
}
