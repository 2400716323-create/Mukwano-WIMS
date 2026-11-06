<?php

namespace App\Http\Requests;

use App\Models\Location;
use App\Models\StockBalance;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PutAwayStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        $balance = $this->route('stockBalance');

        return $balance instanceof StockBalance
            && $balance->location->type === 'bin'
            && ($this->user()?->can('update', $balance->location) ?? false);
    }

    public function rules(): array
    {
        return [
            'destination_location_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')->where('type', 'bin')->where('status', 'active'),
            ],
            'quantity' => ['required', 'numeric', 'gt:0', 'lte:'.($this->route('stockBalance')?->quantity ?? 0), 'decimal:0,3'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $destinationId = $this->input('destination_location_id');
            if ($destinationId === null || $validator->errors()->has('destination_location_id')) {
                return;
            }

            $destination = Location::query()->find($destinationId);
            if ($destination !== null && ! $destination->isOperational()) {
                $validator->errors()->add(
                    'destination_location_id',
                    'Choose a bin with active ancestors.',
                );
            }
        });
    }
}
