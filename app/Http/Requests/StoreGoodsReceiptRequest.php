<?php

namespace App\Http\Requests;

use App\Models\GoodsReceipt;
use App\Models\Location;
use App\Models\PurchaseOrder;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGoodsReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! ($this->user()?->can('create', GoodsReceipt::class) ?? false)) {
            return false;
        }

        return $this->filled('purchase_order_id')
            || ($this->user()?->can('createDirect', GoodsReceipt::class) ?? false);
    }

    public function rules(): array
    {
        return [
            'client_id' => ['nullable', 'uuid'],
            'purchase_order_id' => [
                'nullable',
                'integer',
                Rule::exists('purchase_orders', 'id')->whereIn('status', [
                    PurchaseOrder::STATUS_APPROVED,
                    PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
                ]),
            ],
            'supplier_id' => [
                'required_without:purchase_order_id',
                Rule::prohibitedIf($this->filled('purchase_order_id')),
                'nullable',
                'integer',
                Rule::exists('suppliers', 'id')->where('status', 'active'),
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
            'accept_discrepancies' => ['nullable', 'boolean'],
            'lines' => ['required', 'array', 'min:1', 'max:100'],
            'lines.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('status', 'active'),
            ],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3'],
            'lines.*.batch_number' => ['required', 'string', 'max:100'],
            'lines.*.manufactured_at' => ['nullable', 'date', 'before_or_equal:today'],
            'lines.*.expires_at' => [
                'nullable',
                'date',
                'after_or_equal:today',
                'after_or_equal:lines.*.manufactured_at',
            ],
            'lines.*.location_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')
                    ->where('type', 'receiving')
                    ->where('status', 'active'),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $lines = collect($this->input('lines', []))
                ->filter(fn (mixed $line): bool => is_array($line));

            $products = $lines->pluck('product_id')->filter();

            $batches = $lines->map(
                fn (array $line): string => ($line['product_id'] ?? '').'|'.($line['batch_number'] ?? '')
            );

            if ($batches->duplicates()->isNotEmpty()) {
                $validator->errors()->add(
                    'lines',
                    'Each product and batch combination can only appear once per receipt.'
                );
            }

            foreach ($lines as $index => $line) {
                $location = isset($line['location_id'])
                    ? Location::query()->with('parent')->find($line['location_id'])
                    : null;

                if ($location === null) {
                    continue;
                }

                if (
                    $location->type !== 'receiving'
                    || $location->parent?->type !== 'warehouse'
                    || ! $location->isOperational()
                ) {
                    $validator->errors()->add(
                        "lines.{$index}.location_id",
                        'Choose an active Receiving Area directly under an active warehouse.'
                    );
                }
            }

            $purchaseOrderId = $this->input('purchase_order_id');

            if (! $purchaseOrderId || $validator->errors()->has('purchase_order_id')) {
                return;
            }

            $purchaseOrder = PurchaseOrder::query()
                ->with('lines')
                ->find($purchaseOrderId);

            if ($purchaseOrder === null) {
                return;
            }

            $orderLines = $purchaseOrder->lines->keyBy('product_id');

            foreach ($products->unique() as $productId) {
                if (! $orderLines->has((int) $productId)) {
                    $validator->errors()->add(
                        'lines',
                        'Every received product must appear on the selected purchase order.'
                    );

                    return;
                }

                $receivedUnits = $lines
                    ->filter(
                        fn (array $line): bool => (int) ($line['product_id'] ?? 0) === (int) $productId
                    )
                    ->sum(
                        fn (array $line): int => $this->quantityUnits($line['quantity'] ?? 0)
                    );

                $orderLine = $orderLines->get((int) $productId);

                $outstanding = max(
                    0,
                    $this->quantityUnits($orderLine->ordered_quantity)
                        - $this->quantityUnits($orderLine->received_quantity),
                );

                if ($receivedUnits > $outstanding && ! $this->boolean('accept_discrepancies')) {
                    $validator->errors()->add(
                        'accept_discrepancies',
                        'The receipt exceeds at least one purchase order line. Review and acknowledge the discrepancy.'
                    );

                    return;
                }
            }
        });
    }

    private function quantityUnits(string|int|float $quantity): int
    {
        [$whole, $fraction] = array_pad(
            explode('.', number_format((float) $quantity, 3, '.', '')),
            2,
            '000'
        );

        return ((int) $whole * 1000) + (int) $fraction;
    }
}
