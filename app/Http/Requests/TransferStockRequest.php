<?php

namespace App\Http\Requests;

use App\Models\Location;
use App\Models\StockBalance;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $balance = $this->route('stockBalance');

        if (! $user || ! $balance instanceof StockBalance) {
            return false;
        }

        return $user->hasPermission('Perform stock transfers and bin movements')
            || $user->hasPermission('Receive, transfer, dispatch, and reconcile stock');
    }

    public function rules(): array
    {
        return [
            'destination_location_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')
                    ->where('type', 'bin')
                    ->where('status', 'active'),
            ],
            'quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3'],
            'reason' => ['required', 'string', 'max:1000'],
            'transferred_at' => ['required', 'date', 'before_or_equal:now'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $balance = $this->route('stockBalance');
            $destinationId = $this->input('destination_location_id');
            $quantity = $this->input('quantity');

            if (
                $balance instanceof StockBalance
                && is_numeric($quantity)
                && $this->quantityUnits($quantity) > $this->quantityUnits($balance->quantity)
            ) {
                $validator->errors()->add(
                    'quantity',
                    'The transfer quantity exceeds the available stock.'
                );
            }

            if (
                $destinationId === null
                || $validator->errors()->has('destination_location_id')
            ) {
                return;
            }

            $destination = Location::query()->find($destinationId);

            if ($destination !== null && ! $destination->isOperational()) {
                $validator->errors()->add(
                    'destination_location_id',
                    'The destination bin or one of its ancestors is inactive.'
                );
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
