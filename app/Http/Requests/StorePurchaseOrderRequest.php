<?php

namespace App\Http\Requests;

use App\Models\PurchaseOrder;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $purchaseOrder = $this->route('purchaseOrder');

        return $purchaseOrder instanceof PurchaseOrder
            ? ($this->user()?->can('update', $purchaseOrder) ?? false)
            : ($this->user()?->can('create', PurchaseOrder::class) ?? false);
    }

    public function rules(): array
    {
        return [
            'supplier_id' => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')->where('status', 'active'),
            ],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'lines' => ['required', 'array', 'min:1', 'max:100'],
            'lines.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('status', 'active'),
            ],
            'lines.*.ordered_quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $purchaseOrder = $this->route('purchaseOrder');
            if ($purchaseOrder instanceof PurchaseOrder && $purchaseOrder->status !== PurchaseOrder::STATUS_DRAFT) {
                $validator->errors()->add('status', 'Only draft purchase orders can be edited.');
            }

            $lines = collect($this->input('lines', []))
                ->filter(fn (mixed $line): bool => is_array($line));
            $productIds = $lines
                ->pluck('product_id')
                ->filter()
                ->map(fn (mixed $id): int => (int) $id);

            if ($productIds->duplicates()->isNotEmpty()) {
                $validator->errors()->add('lines', 'A product can only appear once on a purchase order.');
            }
        });
    }
}
